<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Controller;

use Psr\Http\Message\ResponseInterface;
use Ssch\T3Serializer\Domain\Person;
use Symfony\Component\Serializer\Context\Normalizer\ObjectNormalizerContextBuilder;
use Symfony\Component\Serializer\Encoder\JsonEncoder;
use Symfony\Component\Serializer\SerializerInterface;
use TYPO3\CMS\Core\Http\HtmlResponse;
use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;

final class TestController extends ActionController
{
    private SerializerInterface $serializer;

    public function __construct(SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
    }

    public function indexAction(): ResponseInterface
    {
        $context = (new ObjectNormalizerContextBuilder())
            ->toArray();

        $person1 = new Person('Torsten', 'Müller');
        $person2 = new Person('Frank', 'Müller');

        $object = new Person('max', 'mustermann');
        $object->addPerson($person1);
        $object->addPerson($person2);

        $json = $this->serializer->serialize($object, JsonEncoder::FORMAT, $context);
        $objects = $this->serializer->deserialize($json, Person::class, JsonEncoder::FORMAT);

        return new HtmlResponse($json);
    }
}
