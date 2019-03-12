<?php

declare(strict_types=1);

/*
 * The MIT License (MIT)
 *
 * Copyright (c) 2014-2018 Spomky-Labs
 *
 * This software may be modified and distributed under the terms
 * of the MIT license.  See the LICENSE file for details.
 */

namespace Webauthn\Bundle\Controller;

use Assert\Assertion;
use Nyholm\Psr7\Factory\Psr17Factory;
use Symfony\Bridge\PsrHttpMessage\Factory\PsrHttpFactory;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webauthn\AuthenticatorAssertionResponse;
use Webauthn\AuthenticatorAssertionResponseValidator;
use Webauthn\Bundle\Repository\PublicKeyCredentialUserEntityRepository;
use Webauthn\PublicKeyCredentialLoader;
use Webauthn\PublicKeyCredentialRequestOptions;
use Webauthn\PublicKeyCredentialSourceRepository;
use Webauthn\PublicKeyCredentialUserEntity;

final class AssertionResponseController
{
    /**
     * @var PublicKeyCredentialUserEntityRepository
     */
    private $userEntityRepository;

    /**
     * @var PublicKeyCredentialSourceRepository
     */
    private $credentialSourceRepository;

    /**
     * @var PublicKeyCredentialLoader
     */
    private $publicKeyCredentialLoader;

    /**
     * @var AuthenticatorAssertionResponseValidator
     */
    private $assertionResponseValidator;

    public function __construct(PublicKeyCredentialLoader $publicKeyCredentialLoader, AuthenticatorAssertionResponseValidator $assertionResponseValidator, PublicKeyCredentialUserEntityRepository $userEntityRepository, PublicKeyCredentialSourceRepository $credentialSourceRepository)
    {
        $this->assertionResponseValidator = $assertionResponseValidator;
        $this->userEntityRepository = $userEntityRepository;
        $this->credentialSourceRepository = $credentialSourceRepository;
        $this->publicKeyCredentialLoader = $publicKeyCredentialLoader;
    }

    public function __invoke(Request $request): Response
    {
        try {
            $psr17Factory = new Psr17Factory();
            $psrHttpFactory = new PsrHttpFactory($psr17Factory, $psr17Factory, $psr17Factory, $psr17Factory);
            $psr7Request = $psrHttpFactory->createRequest($request);
            Assertion::eq('json', $request->getContentType(), 'Only JSON content type allowed');
            $content = $request->getContent();
            Assertion::string($content, 'Invalid data');
            $publicKeyCredential = $this->publicKeyCredentialLoader->load($content);
            $response = $publicKeyCredential->getResponse();
            Assertion::isInstanceOf($response, AuthenticatorAssertionResponse::class, 'Invalid response');
            $data = $request->getSession()->get('__WEBAUTHN_ASSERTION_REQUEST__');
            $request->getSession()->remove('__WEBAUTHN_ASSERTION_REQUEST__');
            Assertion::isArray($data, 'Unable to find the public key credential creation options');
            Assertion::keyExists($data, 'options', 'Unable to find the public key credential creation options');
            $publicKeyCredentialRequestOptions = $data['options'];
            Assertion::isInstanceOf($publicKeyCredentialRequestOptions, PublicKeyCredentialRequestOptions::class, 'Unable to find the public key credential creation options');
            Assertion::keyExists($data, 'userEntity', 'Unable to find the public key credential creation options');
            $userEntity = $data['userEntity'];
            Assertion::isInstanceOf(PublicKeyCredentialUserEntity::class, $userEntity, 'Unable to find the public key credential creation options');
            Assertion::notEmpty($userEntity, 'Unable to find the public key credential creation options');
            $this->assertionResponseValidator->check($publicKeyCredential->getId(), $response, $publicKeyCredentialRequestOptions, $psr7Request, $userEntity->getId());

            return new JsonResponse(['status' => 'ok', 'errorMessage' => '']);
        } catch (\Throwable $throwable) {
            return new JsonResponse(['status' => 'failed', 'errorMessage' => $throwable->getMessage()], 400);
        }
    }
}