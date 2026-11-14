<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class HideAdminPanel
{
    public function __construct(
        private Environment $twig,
    ) {
    }

    public function notFound(): Response
    {
        return new Response(
            $this->twig->render('error/not_found.html.twig'),
            Response::HTTP_NOT_FOUND,
        );
    }
}
