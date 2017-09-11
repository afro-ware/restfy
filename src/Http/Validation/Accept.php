<?php

namespace Afroware\Restfy\Http\Validation;

use Illuminate\Http\Request;
use Afroware\Restfy\Contract\Http\Validator;
use Afroware\Restfy\Http\Parser\Accept as AcceptParser;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;

class Accept implements Validator
{
    /**
     * Accept parser instance.
     *
     * @var \Afroware\Restfy\Http\Parser\Accept
     */
    protected $accept;

    /**
     * Indicates if the accept matching is strict.
     *
     * @var bool
     */
    protected $strict;

    /**
     * Create a new accept validator instance.
     *
     * @param \Afroware\Restfy\Http\Parser\Accept $accept
     * @param bool                          $strict
     *
     * @return void
     */
    public function __construct(AcceptParser $accept, $strict = false)
    {
        $this->accept = $accept;
        $this->strict = $strict;
    }

    /**
     * Validate the accept header on the request. If this fails it will throw
     * an HTTP exception that will be caught by the middleware. This
     * validator should always be run last and must not return
     * a success boolean.
     *
     * @param \Illuminate\Http\Request $request
     *
     * @throws \Symfony\Component\HttpKernel\Exception\BadRequestHttpException
     *
     * @return bool
     */
    public function validate(Request $request)
    {
        try {
            $this->accept->parse($request, $this->strict);
        } catch (BadRequestHttpException $exception) {
            if ($request->getMethod() === 'OPTIONS') {
                return true;
            }

            throw $exception;
        }
    }
}
