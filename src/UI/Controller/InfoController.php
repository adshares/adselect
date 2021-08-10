<?php

declare(strict_types=1);

namespace Adshares\AdSelect\UI\Controller;

use Adshares\AdSelect\Lib\InfoHelper;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class InfoController
{
    public function info(Request $request): Response
    {
        srand(crc32($request->getClientIp() . date('-d-m-Y-h')));
        $info = [
            'module' => 'adselect',
            'name' => $_ENV['APP_NAME'],
            'version' => $_ENV['APP_VERSION'],
        ];

        return new Response(
            $request->getRequestFormat() === 'txt' ? InfoHelper::formatTxt($info) : self::formatJson($info)
        );
    }

    /** @param array<string> $data */
    private static function formatJson(array $data): string
    {
        return json_encode($data);
    }
}
