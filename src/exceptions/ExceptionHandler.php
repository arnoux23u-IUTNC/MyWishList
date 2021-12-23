<?php

namespace mywishlist\exceptions;

class ExceptionHandler
{

    private static array $lang;

    public function __construct(array $lang)
    {
        self::$lang = $lang;
    }

    public function __invoke($request, $response, $exception) {
        $lang = self::$lang;
        if ($exception instanceof ForbiddenException) {
            $title = $exception->getTitle();
            $msg = $exception->getMessage();
            $backRoute = $request->getHeader("HTTP_REFERER")[0] ?? "/";
            return $response->write(genererHeader($title, ["style.css"]) . "\t<div class='container_error'>\n\t\t<img alt='forbidden' class='forbidden' src='/assets/img/forbidden.png'>\n\t\t<h4>$msg</h4>\n\t\t<span><a id='backBtn' content='{$lang['html_btn_back']}' href='$backRoute'></a></span>\n\t</div>\n</body>\n</html>")->withStatus(403);
        }
        return $response->withStatus(500)->withHeader('Content-Type', 'text/html')->write($exception->getMessage());
   }
}