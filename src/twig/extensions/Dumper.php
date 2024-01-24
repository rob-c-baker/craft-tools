<?php
declare(strict_types=1);

namespace alanrogers\tools\twig\extensions;

use Craft;
use Exception;
use Twig\Environment;
use Twig\Extension\AbstractExtension;
use Twig\Template;
use Twig\TwigFunction;
use alanrogers\tools\services\Dumper as DumperService;

class Dumper extends AbstractExtension
{
    public function getFunctions() : array
    {
        // dump is safe if var_dump is overridden by xdebug
        $isDumpOutputHtmlSafe = extension_loaded('xdebug')
            // false means that it was not set (and the default is on) or it explicitly enabled
            && (false === ini_get('xdebug.overload_var_dump') || ini_get('xdebug.overload_var_dump'))
            // false means that it was not set (and the default is on) or it explicitly enabled
            // xdebug.overload_var_dump produces HTML only when html_errors is also enabled
            && (false === ini_get('html_errors') || ini_get('html_errors'))
            || 'cli' === PHP_SAPI;

        $options = [
            'is_safe' => $isDumpOutputHtmlSafe ? ['html'] : [],
            'needs_context' => true,
            'needs_environment' => true,
            'debug' => true,
        ];

        return [
            new TwigFunction('d', [Dumper::class, 'd'], $options),
            new TwigFunction('dd', [Dumper::class, 'dd'], $options),
            new TwigFunction('dump', [Dumper::class, 'dump'], [
                'is_safe'           => $isDumpOutputHtmlSafe ? ['html'] : [],
                'needs_context'     => true,
                'needs_environment' => true,
            ]),
            new TwigFunction('trace', [Dumper::class, 'trace'], [
                'needs_environment' => true,
            ])
        ];
    }

    /**
     * Outputs a stack trace - useful for finding what Twig templates are calling the current.
     * @param Environment $env
     * @param bool $die
     * @return string
     */
    public static function trace(Environment $env, bool $die=true): void
    {
        if (!$env->isDebug()) {
            return;
        }
        $ex = new Exception();
        $css = '<style>' . self::getTraceCSS() . '</style>';
        $stack = '<div class="call-stack">' . Craft::$app->errorHandler->renderCallStack($ex) . '</div>';
        echo $css . $stack;
        if ($die) {
            die;
        }
    }

    /**
     * Shorthand for `dump()`
     * @param Environment $env
     * @param array $context
     * @param mixed ...$items
     */
    public static function d(Environment $env, array $context, ...$items): void
    {
        if (!$env->isDebug()) {
            return;
        }

        if (!$items) {
            // No parameters passed in - dump entire twig context instead
            $items = self::collectContext($context);
        }

        DumperService::instance()->d(...$items);
    }

    /**
     * Dump and die!
     * @param Environment $env
     * @param array $context
     * @param mixed ...$items
     */
    public static function dd(Environment $env, array $context, ...$items): void
    {
        if (!$env->isDebug()) {
            return;
        }

        if (!$items) {
            // No parameters passed in - dump entire twig context instead
            $items = self::collectContext($context);
        }

        DumperService::instance()->dd(...$items);
    }

    /**
     * Override dump version of Symfony's VarDumper component
     *
     * @param Environment $env
     * @param array $context
     * @param mixed ...$items
     */
    public static function dump(Environment $env, array $context, ...$items) : void
    {
        if (!$env->isDebug()) {
            return;
        }

        if (!$items) {
            // No parameters passed in - dump entire twig context instead
            $items = self::collectContext($context);
        }

        DumperService::instance()->dump(...$items);
    }

    private static function collectContext(array $context) : array
    {
        $items = [];
        foreach ($context as $key => $value) {
            if (!$value instanceof Template) {
                $items[$key] = $value;
            }
        }
        return $items;
    }

    private static function getTraceCSS(): string
    {
        return <<<CSS


/* base */
a{
    text-decoration: none;
}
a:hover{
    text-decoration: underline;
}
h1,h2,h3,p,img,ul li{
    font-family: Arial,sans-serif;
    color: #505050;
}
/*corresponds to min-width of 860px for some elements (.header .footer .element ...)*/
@media screen and (min-width: 960px) {
    html,body{
        overflow-x: hidden;
    }
}

/* header */
.header{
    min-width: 860px; /* 960px - 50px * 2 */
    margin: 0 auto;
    background: #f3f3f3;
    padding: 40px 50px 30px 50px;
    border-bottom: #ccc 1px solid;
}
.header h1{
    font-size: 30px;
    color: #e57373;
    margin-bottom: 30px;
}
.header h1 span, .header h1 span a{
    color: #e51717;
}
.header h1 a{
    color: #e57373;
}
.header h1 a:hover{
    color: #e51717;
}
.header img.erroricon{
    float: right;
    margin-top: -15px;
    margin-left: 50px;
}
.header .tools{
    float: right;
}
.header .tools a {
    border-radius: 5px;
    width: 25px;
    text-align: center;
    margin-right: 7px;
}
.header .tools a,
.header .tools span{
    display: block;
    float: left;
    height: 25px;
    padding: 5px;
}
.header .tools span{
    display: none;
}
.header .tools a:hover{
    background: #fff;
    text-decoration: none;
}
.header .tools a:active img{
    position: relative;
    left: 2px;
    top: 2px;
}
.header .tools textarea{
    position: absolute;
    top: -500px;
    right: 300px;
    width: 750px;
    height: 150px;
}
.header h2{
    font-size: 20px;
    line-height: 1.25;
}
.header pre{
    margin: 10px 0;
    overflow-y: scroll;
    font-family: Courier, monospace;
    font-size: 14px;
}

/* previous exceptions */
.header .previous{
    margin: 20px 0;
    padding-left: 30px;
}
.header .previous div{
    margin: 20px 0;
}
.header .previous .arrow{
    -moz-transform: scale(-1, 1);
    -webkit-transform: scale(-1, 1);
    -o-transform: scale(-1, 1);
    transform: scale(-1, 1);
    filter: progid:DXImageTransform.Microsoft.BasicImage(mirror=1);
    font-size: 26px;
    position: absolute;
    margin-top: -3px;
    margin-left: -30px;
    color: #e51717;
}
.header .previous h2{
    font-size: 20px;
    color: #e57373;
    margin-bottom: 10px;
}
.header .previous h2 span{
    color: #e51717;
}
.header .previous h2 a{
    color: #e57373;
}
.header .previous h2 a:hover{
    color: #e51717;
}
.header .previous h3{
    font-size: 14px;
    margin: 10px 0;
}
.header .previous p{
    font-size: 14px;
    color: #aaa;
}
.header .previous pre{
    font-family: Courier, monospace;
    font-size: 14px;
    margin: 10px 0;
}

/* call stack */
.call-stack{
    margin-top: 30px;
    margin-bottom: 40px;
}
.call-stack ul li{
    margin: 1px 0;
}
.call-stack ul li .element-wrap{
    cursor: pointer;
    padding: 15px 0;
    background-color: #fdfdfd;
}
.call-stack ul li.application .element-wrap{
    background-color: #fafafa;
}
.call-stack ul li .element-wrap:hover{
    background-color: #edf9ff;
}
.call-stack ul li .element{
    min-width: 860px; /* 960px - 50px * 2 */
    margin: 0 auto;
    padding: 0 50px;
    position: relative;
}
.call-stack ul li a{
    color: #505050;
}
.call-stack ul li a:hover{
    color: #000;
}
.call-stack ul li .item-number{
    width: 45px;
    display: inline-block;
}
.call-stack ul li .text{
    color: #aaa;
}
.call-stack ul li.application .text{
    color: #505050;
}
.call-stack ul li .at{
    float: right;
    display: inline-block;
    width: 7em;
    padding-left: 1em;
    text-align: left;
    color: #aaa;
}
.call-stack ul li.application .at{
    color: #505050;
}
.call-stack ul li .line{
    display: inline-block;
    width: 3em;
    text-align: right;
}
.call-stack ul li .code-wrap{
    display: none;
    position: relative;
}
.call-stack ul li.application .code-wrap{
    display: block;
}
.call-stack ul li .error-line,
.call-stack ul li .hover-line{
    background-color: #ffebeb;
    position: absolute;
    width: 100%;
    z-index: 100;
    margin-top: 0;
}
.call-stack ul li .hover-line{
    background: none;
}
.call-stack ul li .hover-line.hover,
.call-stack ul li .hover-line:hover{
    background: #edf9ff !important;
}
.call-stack ul li .code{
    min-width: 860px; /* 960px - 50px * 2 */
    margin: 15px auto;
    padding: 0 50px;
    position: relative;
}
.call-stack ul li .code .lines-item{
    z-index: 200;
    display: block;
    width: 25px;
    text-align: right;
    color: #aaa;
    line-height: 20px;
    font-size: 12px;
    margin-top: 1px;
    font-family: Consolas, monospace;
}
.call-stack ul li .code pre{
    position: absolute;
    z-index: 200;
    left: 50px;
    line-height: 21px;
    font-size: 12px;
    font-family: Consolas, monospace;
    display: inline;
    top: 0;
    left: 6rem;
    margin: 0;
}
@-moz-document url-prefix() {
    .call-stack ul li .code pre{
        line-height: 20px;
    }
}

/* request */
.request{
    background-color: #fafafa;
    padding-top: 40px;
    padding-bottom: 40px;
    margin-top: 40px;
    margin-bottom: 1px;
}
.request .code{
    min-width: 860px; /* 960px - 50px * 2 */
    margin: 0 auto;
    padding: 15px 50px;
}
.request .code pre{
    font-size: 14px;
    line-height: 18px;
    font-family: Consolas, monospace;
    display: inline;
    word-wrap: break-word;
}

/* footer */
.footer{
    position: relative;
    height: 222px;
    min-width: 860px; /* 960px - 50px * 2 */
    padding: 0 50px;
    margin: 1px auto 0 auto;
}
.footer p{
    font-size: 16px;
    padding-bottom: 10px;
}
.footer p a{
    color: #505050;
}
.footer p a:hover{
    color: #000;
}
.footer .timestamp{
    font-size: 14px;
    padding-top: 67px;
    margin-bottom: 28px;
}
.footer img{
    position: absolute;
    right: -50px;
}

/* highlight.js */
.comment{
    color: #808080;
    font-style: italic;
}
.keyword{
    color: #000080;
}
.number{
    color: #00a;
}
.number{
    font-weight: normal;
}
.string, .value{
    color: #0a0;
}
.symbol, .char {
    color: #505050;
    background: #d0eded;
    font-style: italic;
}
.phpdoc{
    text-decoration: underline;
}
.variable{
    color: #a00;
}

body pre {
    pointer-events: none;
}
body.mousedown pre {
    pointer-events: auto;
}
CSS;
    }
}