<?php

namespace App\Security;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

final class HideAdminAccessDeniedHandler implements AccessDeniedHandlerInterface
{
    public function __construct(
        private HideAdminPanel $hideAdminPanel,
    ) {
    }

    public function handle(Request $request, AccessDeniedException $accessDeniedException): Response
    {
        return $this->hideAdminPanel->notFound();
    }
}
