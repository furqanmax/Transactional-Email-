<?php

namespace Furqanmax\TransactionalEmail\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @method static string login(?string $email = null, ?string $password = null)
 * @method static array sendTemplateEmail(string $from, string $to, string $templateKey, array|string $templateVariables, ?string $subject = null, ?string $preheaderText = null, ?string $uuid = null, ?string $token = null)
 * @method static array sendDirectEmail(string $from, string $to, string $subject, ?string $preheader = null, string $body = '', ?string $htmlBody = null, ?string $token = null)
 * @method static void setToken(?string $token)
 * @method static ?string getToken()
 *
 * @see \Furqanmax\TransactionalEmail\TransactionalEmailClient
 */
class TransactionalEmail extends Facade
{
    protected static function getFacadeAccessor(): string
    {
        return \Furqanmax\TransactionalEmail\TransactionalEmailClient::class;
    }
}

