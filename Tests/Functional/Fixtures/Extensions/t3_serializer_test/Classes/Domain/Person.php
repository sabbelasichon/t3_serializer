<?php

declare(strict_types=1);

/*
 * This file is part of the "t3_serializer" Extension for TYPO3 CMS.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 */

namespace Ssch\T3Serializer\Tests\Functional\Fixtures\Extensions\t3_serializer_test\Classes\Domain;

use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use TYPO3\CMS\Extbase\Persistence\ObjectStorage;

final class Person
{
    /**
     * @Groups({"default"})
     */
    private string $firstName;

    private string $lastName;

    /**
     * @var ObjectStorage<Person>
     */
    private ObjectStorage $persons;

    public function __construct(string $firstName, string $lastName)
    {
        $this->firstName = $firstName;
        $this->lastName = $lastName;
        $this->persons = new ObjectStorage();
    }

    public function getFirstName(): string
    {
        return $this->firstName;
    }

    public function getLastName(): string
    {
        return $this->lastName;
    }

    /**
     * @return Person[]
     */
    public function getPersons(): array
    {
        return $this->persons->toArray();
    }

    public function addPerson(self $person): void
    {
        $this->persons->attach($person);
    }

    public function setPersons(array $persons): void
    {
        foreach ($persons as $person) {
            $this->persons->attach($person);
        }
    }

    /**
     * @SerializedName("salutation")
     */
    public function getGender(): string
    {
        return 'Mr';
    }
}
