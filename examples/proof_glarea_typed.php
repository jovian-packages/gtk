<?php
/*
 * proof_glarea_typed.php — the windowed GL proof on the typed surface.
 *
 * Port of ext-gtk's examples/proof_glarea.php onto jovian/gtk DTOs: a
 * GtkWindow holding a GtkGLArea, the render signal reached through the
 * generated `onRender()` projection, and the GdkGLContext boxed by the
 * Registry into a typed DTO. In the render handler we call OpenGL against
 * the framebuffer GTK has already made current: clear it, draw a triangle,
 * then glReadPixels the centre and a corner back and byte-check them on
 * the first rendered frame. The window stays up for ~2s of real frames
 * pumped from PHP.
 *
 * WHY THE GL CALLS ARE RAW. jovian/ogx — the typed projection of
 * ext-opengl — is not wired into surface-dev, so the GL half of this proof
 * calls the extension classes (OpenGL\GL\GL10\GL10 and friends) directly
 * while the GTK half is fully typed. That asymmetry is deliberate and
 * temporary: when jovian/ogx is path-linked here, only the `use` lines and
 * the call sites change, not the shape of the proof.
 *
 * ON THE API GDK HANDS YOU. GtkGLArea does not necessarily give desktop
 * GL. On the Pi 5 (Mesa V3D) GDK can only create a GLES 3.1 context for a
 * GtkGLArea; narrowing allowed-apis to GDK_GL_API_GL leaves getContext()
 * null and render never fires. See ext-gtk
 * .okf/traps/glarea-is-gles-on-the-pi.md. This file therefore allows both
 * and picks the shader dialect from the API the context reports.
 *
 * Needs the box's logged-in seat, plus both extensions:
 *   XDG_RUNTIME_DIR=/run/user/1000 DISPLAY=:0 php examples/proof_glarea_typed.php
 *
 * GL constants are inline ints citing ext-opengl's vendored
 * scripts/khronos/glcorearb.h by line — they belong to jovian/ogx.
 *
 * Exit codes: 0 = PROOF_GLAREA_TYPED_OK, 1 = failure.
 */

declare(strict_types=1);

use Jovian\Bindings\Gtk\Enums\GdkGLAPI;
use Jovian\Bindings\Gtk\Gdk\GdkGLContext;
use Jovian\Bindings\Gtk\Gtk\GtkGLArea;
use Jovian\Bindings\Gtk\Gtk\GtkWindow;
use Jovian\Bindings\Gtk\Runtime\Bridge;
use Jovian\Bindings\Gtk\Runtime\Lifetime;
use Jovian\Bindings\Gtk\Runtime\Registry;

use OpenGL\Bridge\Bridge as GLBridge;
use OpenGL\GL\GL10\GL10;
use OpenGL\GL\GL11\GL11;
use OpenGL\GL\GL15\GL15;
use OpenGL\GL\GL20\GL20;
use OpenGL\GL\GL30\GL30;

/* ---- glcorearb.h (ext-opengl/scripts/khronos/glcorearb.h) ----------- */
const GL_COLOR_BUFFER_BIT = 0x00004000;          // glcorearb.h:74
const GL_TRIANGLES = 0x0004;                     // glcorearb.h:81
const GL_NO_ERROR = 0;                           // glcorearb.h:114
const GL_UNSIGNED_BYTE = 0x1401;                 // glcorearb.h:187
const GL_FLOAT = 0x1406;                         // glcorearb.h:192
const GL_RGBA = 0x1908;                          // glcorearb.h:222
const GL_RENDERER = 0x1F01;                      // glcorearb.h:231
const GL_VERSION = 0x1F02;                       // glcorearb.h:232
const GL_ARRAY_BUFFER = 0x8892;                  // glcorearb.h:606
const GL_STATIC_DRAW = 0x88E4;                   // glcorearb.h:620
const GL_FRAGMENT_SHADER = 0x8B30;               // glcorearb.h:709
const GL_VERTEX_SHADER = 0x8B31;                 // glcorearb.h:710
const GL_COMPILE_STATUS = 0x8B81;                // glcorearb.h:737
const GL_LINK_STATUS = 0x8B82;                   // glcorearb.h:738
const GL_INFO_LOG_LENGTH = 0x8B84;               // glcorearb.h:740
const GL_SHADING_LANGUAGE_VERSION = 0x8B8C;      // glcorearb.h:748

const AREA_W = 320;
const AREA_H = 240;

/*
 * Standalone checkout (Mac) uses this package's own vendor/; on the Pi
 * this package is path-linked into surface-dev, which owns the vendor
 * tree — same candidate-list idiom as gtkExtRoot() in tests/Pest.php.
 */
$autoload = null;
foreach ([dirname(__DIR__) . '/vendor/autoload.php', dirname(__DIR__, 3) . '/vendor/autoload.php'] as $candidate) {
    if (is_file($candidate)) {
        $autoload = $candidate;
        break;
    }
}
if (is_null($autoload)) {
    fwrite(STDERR, "proof_glarea_typed: no autoloader found — run composer install\n");
    exit(1);
}
require $autoload;

$allocated = [];

function buffer(int $bytes): int
{
    global $allocated;

    $ptr = GLBridge::alloc($bytes);
    if ($ptr === 0) {
        throw new RuntimeException("OpenGL Bridge::alloc({$bytes}) failed");
    }
    $allocated[] = $ptr;

    return $ptr;
}

function readInt(int $ptr, int $offset = 0): int
{
    return unpack('l', GLBridge::read($ptr, $offset, 4))[1];
}

function fail(string $why): never
{
    fwrite(STDERR, "proof_glarea_typed: {$why}\n");
    fwrite(STDERR, "PROOF_GLAREA_TYPED_FAILED\n");
    exit(1);
}

function step(string $what): void
{
    echo "  {$what}\n";
}

function compileShader(int $stage, string $source, string $label): int
{
    $shader = GL20::glCreateShader($stage);
    if ($shader === 0) {
        throw new RuntimeException("glCreateShader({$label}) returned 0");
    }

    GL20::glShaderSource($shader, 1, [$source], 0);
    GL20::glCompileShader($shader);

    $status = buffer(4);
    GL20::glGetShaderiv($shader, GL_COMPILE_STATUS, $status);
    if (readInt($status) === 1) {
        return $shader;
    }

    GL20::glGetShaderiv($shader, GL_INFO_LOG_LENGTH, $status);
    $len = max(1, readInt($status));
    $log = buffer($len);
    GL20::glGetShaderInfoLog($shader, $len, 0, $log);

    throw new RuntimeException(
        "{$label} shader did not compile:\n" . rtrim(GLBridge::read($log, 0, $len), "\0")
    );
}

// ---------------------------------------------------------------- setup

echo "proof_glarea_typed — " . PHP_OS_FAMILY . ' / ' . php_uname('m') . "\n\n";

foreach (['gtk', 'opengl'] as $ext) {
    if (!extension_loaded($ext)) {
        fail("the {$ext} extension is not loaded");
    }
}
step('gtk ' . phpversion('gtk') . ', opengl ' . phpversion('opengl')
    . ' — GTK typed through jovian/gtk, GL raw (jovian/ogx not wired in)');

try {
    Lifetime::boot();
} catch (Throwable $e) {
    fail('Lifetime::boot() failed: ' . $e->getMessage());
}
if (!Lifetime::isBooted()) {
    fail('gtk_init_check failed — run from the logged-in seat');
}
step('Lifetime::boot() ok');

echo "\n1. typed window + GtkGLArea\n";

$win = GtkWindow::new();
$win->setTitle('jovian/gtk — GtkGLArea + ext-opengl');
$win->setDefaultSize(AREA_W, AREA_H);

$area = GtkGLArea::new();

/*
 * Both APIs allowed. GdkGLAPI is a GIR bitfield, so the enum exists for
 * naming but the parameter stays int — PHP enums cannot be OR'd, which is
 * exactly the rule .okf/enums-and-contracts.md states.
 */
$area->setAllowedApis(GdkGLAPI::GL->value | GdkGLAPI::GLES->value)
    ->setAutoRender(true)
    ->setHasDepthBuffer(false)
    ->setHasStencilBuffer(false)
    ->setRequiredVersion(3, 0);

$required = $area->getRequiredVersion();
step("GtkGLArea: allowed-apis={$area->getAllowedApis()}"
    . ", required {$required['major']}.{$required['minor']}"
    . ', auto-render=' . ($area->getAutoRender() ? 'true' : 'false'));

$area->setHexpand(true)->setVexpand(true);
$win->setChild($area);

step('typeName=' . $area->typeName() . ', isA(GtkWidget)=' . ($area->isA('GtkWidget') ? 'true' : 'false'));

echo "\n2. onRender (the typed signal projection)\n";

$state = [
    'frames' => 0,
    'checked' => false,
    'program' => 0,
    'error' => null,
    'context' => null,
];

/*
 * The render signal returns gboolean and a handler that drew the frame
 * itself must return true. The generated onRender() is a plain
 * Bridge::connect projection, so the extension's return writeback carries
 * the PHP bool straight into GTK — the same path close-request uses in
 * examples/smoke-dto.php.
 */
$area->onRender(static function (int $sender, int $contextHandle) use (&$state, $area): bool {
    if ($state['error'] !== null) {
        return true;
    }

    try {
        $state['frames']++;

        if ($state['program'] === 0) {
            if (!GLBridge::load()) {
                throw new RuntimeException('OpenGL Bridge::load() could not open an OpenGL library');
            }

            /*
             * Box the signal's context handle through the Registry. GDK
             * hands out a backend subclass (GdkWaylandGLContext /
             * GdkX11GLContext) whose GType is not in the generated map, so
             * this exercises TypeMap::resolveWithProbe — the same
             * mechanism the media backends needed.
             */
            $context = Registry::box($contextHandle);
            if (!$context instanceof GdkGLContext) {
                throw new RuntimeException(
                    'Registry::box did not resolve the context to a GdkGLContext; got '
                    . (is_null($context) ? 'null' : $context::class)
                );
            }

            $api = $context->getApi();
            $version = $context->getVersion();
            $state['context'] = [
                'class' => $context::class,
                'typeName' => $context->typeName(),
                'api' => $api,
                'apiName' => GdkGLAPI::tryFrom($api)?->name ?? "unknown({$api})",
                'version' => "{$version['major']}.{$version['minor']}",
                'legacy' => $context->isLegacy(),
                'useEs' => $context->getUseEs(),
                'sameArea' => Registry::box($sender) === $area,
            ];
            if ($version['major'] < 3) {
                throw new RuntimeException("this proof needs a 3.0+ context; got {$state['context']['version']}");
            }

            $glsl = (string) GL10::glGetString(GL_SHADING_LANGUAGE_VERSION);
            $state['strings'] = [
                'version' => (string) GL10::glGetString(GL_VERSION),
                'renderer' => (string) GL10::glGetString(GL_RENDERER),
                'glsl' => $glsl,
            ];

            // The shader dialect follows the API the context reports.
            $es = $api === GdkGLAPI::GLES->value;
            $directive = $es
                ? '#version 300 es'
                : (version_compare($glsl, '1.50', '<') ? '#version 140' : '#version 150 core');
            $precision = $es ? "precision mediump float;\n" : '';
            $state['directive'] = $directive;

            $vaoOut = buffer(4);
            GL30::glGenVertexArrays(1, $vaoOut);
            $vao = readInt($vaoOut);
            GL30::glBindVertexArray($vao);

            $vertices = pack('f*', 0.0, 0.8, -0.8, -0.8, 0.8, -0.8);
            $vboOut = buffer(4);
            GL15::glGenBuffers(1, $vboOut);
            $vbo = readInt($vboOut);
            GL15::glBindBuffer(GL_ARRAY_BUFFER, $vbo);
            $vertexData = buffer(strlen($vertices));
            GLBridge::write($vertexData, 0, $vertices);
            GL15::glBufferData(GL_ARRAY_BUFFER, strlen($vertices), $vertexData, GL_STATIC_DRAW);

            $vs = compileShader(
                GL_VERTEX_SHADER,
                "{$directive}\n"
                . "in vec2 aPos;\n"
                . "void main()\n"
                . "{\n"
                . "    gl_Position = vec4(aPos, 0.0, 1.0);\n"
                . "}\n",
                'vertex'
            );

            $fs = compileShader(
                GL_FRAGMENT_SHADER,
                "{$directive}\n"
                . $precision
                . "out vec4 fragColour;\n"
                . "void main()\n"
                . "{\n"
                . "    fragColour = vec4(1.0, 0.5, 0.25, 1.0);\n"
                . "}\n",
                'fragment'
            );

            $program = GL20::glCreateProgram();
            GL20::glAttachShader($program, $vs);
            GL20::glAttachShader($program, $fs);
            GL20::glBindAttribLocation($program, 0, 'aPos');
            GL20::glLinkProgram($program);

            $status = buffer(4);
            GL20::glGetProgramiv($program, GL_LINK_STATUS, $status);
            if (readInt($status) !== 1) {
                GL20::glGetProgramiv($program, GL_INFO_LOG_LENGTH, $status);
                $len = max(1, readInt($status));
                $log = buffer($len);
                GL20::glGetProgramInfoLog($program, $len, 0, $log);
                throw new RuntimeException(
                    "program did not link:\n" . rtrim(GLBridge::read($log, 0, $len), "\0")
                );
            }

            GL20::glUseProgram($program);
            GL20::glEnableVertexAttribArray(0);
            GL20::glVertexAttribPointer(0, 2, GL_FLOAT, false, 0, 0);
            $state['program'] = $program;
            $state['vao'] = $vao;
        }

        // ---- draw into the GLArea's own framebuffer
        $w = max(1, $area->getWidth());
        $h = max(1, $area->getHeight());

        GL20::glUseProgram($state['program']);
        GL30::glBindVertexArray($state['vao']);
        GL10::glViewport(0, 0, $w, $h);
        GL10::glClearColor(0.0, 0.0, 0.0, 1.0);
        GL10::glClear(GL_COLOR_BUFFER_BIT);
        GL11::glDrawArrays(GL_TRIANGLES, 0, 3);

        $err = GL10::glGetError();
        if ($err !== GL_NO_ERROR) {
            throw new RuntimeException(sprintf('glGetError() = 0x%X after the draw', $err));
        }

        if (!$state['checked']) {
            GL10::glFinish();

            $pixels = buffer($w * $h * 4);
            GL10::glReadPixels(0, 0, $w, $h, GL_RGBA, GL_UNSIGNED_BYTE, $pixels);

            $centre = GLBridge::read($pixels, (intdiv($h, 2) * $w + intdiv($w, 2)) * 4, 4);
            $corner = GLBridge::read($pixels, 0, 4);
            if (is_null($centre) || is_null($corner)) {
                throw new RuntimeException('OpenGL Bridge::read of the pixel buffer returned null');
            }

            $state['centre'] = array_values(unpack('C4', $centre));
            $state['corner'] = array_values(unpack('C4', $corner));
            $state['size'] = [$w, $h];
            $state['checked'] = true;
        }
    } catch (Throwable $e) {
        $state['error'] = $e->getMessage();
    }

    return true;
});

$resizes = 0;
$area->onResize(static function (int $sender, int $w, int $h) use (&$resizes): void {
    $resizes++;
});
step('connected onRender + onResize');

echo "\n3. present and pump ~2s of real frames\n";

$win->present();

$deadline = microtime(true) + 2.0;
while (microtime(true) < $deadline) {
    Bridge::pump(50);
    if ($state['error'] !== null) {
        break;
    }
    $area->queueRender();
}

$visible = $win->getVisible();
$win->close();
Bridge::pump(200);

foreach ($allocated as $ptr) {
    GLBridge::free($ptr);
}

// ---------------------------------------------------------------- verdict

echo "\n4. what happened\n";

if ($state['error'] !== null) {
    fail($state['error']);
}
if (!$visible) {
    fail('the window never became visible');
}
if ($state['frames'] === 0) {
    fail('onRender never fired — no frames were drawn (getContext = '
        . (is_null($area->getContext()) ? 'null' : 'set') . ')');
}
if (!$state['checked']) {
    fail('no frame was read back');
}

$ctx = $state['context'];
step("context boxed to {$ctx['class']} (GType {$ctx['typeName']})");
step("api={$ctx['api']} (GdkGLAPI::{$ctx['apiName']}), version {$ctx['version']}"
    . ', legacy=' . ($ctx['legacy'] ? 'true' : 'false')
    . ', use-es=' . ($ctx['useEs'] ? 'true' : 'false'));
step('signal sender boxed back to the same GtkGLArea: ' . ($ctx['sameArea'] ? 'true' : 'false'));
step("GL_VERSION                  = {$state['strings']['version']}");
step("GL_RENDERER                 = {$state['strings']['renderer']}");
step("GL_SHADING_LANGUAGE_VERSION = {$state['strings']['glsl']}");
step("shader directive            = {$state['directive']}");
step("frames rendered             = {$state['frames']} (resize fired {$resizes}x)");
step("framebuffer                 = {$state['size'][0]}x{$state['size'][1]}");

$c = $state['centre'];
$k = $state['corner'];
step(sprintf('centre RGBA = %d,%d,%d,%d', $c[0], $c[1], $c[2], $c[3]));
step(sprintf('corner RGBA = %d,%d,%d,%d', $k[0], $k[1], $k[2], $k[3]));

if (!$ctx['sameArea']) {
    fail('the render sender did not box back to the GtkGLArea it was connected on');
}
// The shader writes (1.0, 0.5, 0.25, 1.0); the clear is opaque black.
if ($c[0] < 240 || $c[1] < 100 || $c[1] > 155 || $c[2] < 48 || $c[2] > 80 || $c[3] !== 255) {
    fail('the centre pixel is not the shader colour');
}
if ($k[0] !== 0 || $k[1] !== 0 || $k[2] !== 0 || $k[3] !== 255) {
    fail('the corner pixel is not the clear colour');
}
step('centre is the shader colour and the corner is the clear colour');

echo "\nPROOF_GLAREA_TYPED_OK\n";
exit(0);
