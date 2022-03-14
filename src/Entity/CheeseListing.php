<?php

namespace App\Entity;

use ApiPlatform\Core\Annotation\ApiFilter;
use ApiPlatform\Core\Annotation\ApiResource;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\BooleanFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\RangeFilter;
use ApiPlatform\Core\Bridge\Doctrine\Orm\Filter\SearchFilter;
use ApiPlatform\Core\Serializer\Filter\PropertyFilter;
use Carbon\Carbon;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\CheeseListingRepository;
use DateTimeInterface;
use DateTimeImmutable;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * @ORM\Entity(repositoryClass=CheeseListingRepository::class)
 * @ORM\Table(name="cheese_listing")
 */
#[ApiResource(
    collectionOperations: [
        'get',
        'post' => [
            'security' => 'is_granted("ROLE_USER")'
        ]
    ],
    itemOperations: [
        'get' => [
            'normalization_context' => [
                'groups' => ['cheeses:read', 'cheeses:item:get'],
                'swagger_definition_name' => 'item-get'
            ]
        ],
        'put' => [
            'security' => 'is_granted("CHEESE_EDIT", object)',
            'security_message' => 'Only author can edit this cheese listing'
        ],
        'delete' => [
            'security' => 'is_granted("ROLE_ADMIN")'
        ]
    ],
    shortName: 'cheeses',
    attributes: [
        'pagination_items_per_page' => 10,
        'formats' => ['jsonld', 'json', 'html', 'jsonhal', 'csv' => ['text/csv']]
    ],
    denormalizationContext: ['groups' => ['cheeses:write']],
    normalizationContext: ['groups' => ['cheeses:read']],
)]
#[ApiFilter(BooleanFilter::class, properties: ['isPublished'])]
#[ApiFilter(
    SearchFilter::class,
    properties: [
        'title' => 'partial',
        'owner' => 'exact',
        'owner.username' => 'partial'
    ]
)]
#[ApiFilter(RangeFilter::class, properties: ['price'])]
#[ApiFilter(PropertyFilter::class)]
class CheeseListing
{
    /**
     * @ORM\Id()
     * @ORM\GeneratedValue()
     * @ORM\Column(type="integer")
     */
    private ?int $id = null;

    /**
     * @ORM\Column(type="string", length=255)
     * @Assert\NotBlank()
     * @Assert\Length(
     *     min=2,
     *     max=50,
     *     maxMessage="Maximum 50 caracteres ou moins."
     * )
     */
    #[Groups(['cheeses:read', 'cheeses:write', 'user:read', 'user:write'])]
    private ?string $title = null;

    /**
     * @ORM\Column(type="text")
     * @Assert\NotBlank()
     */
    #[Groups(['cheeses:read'])]
    private ?string $description = null;

    /**
     * @ORM\Column(type="integer")
     * @Assert\NotBlank()
     */
    #[Groups(['cheeses:read', 'cheeses:write', 'user:read', 'user:write'])]
    private ?int $price = null;

    /**
     * @ORM\Column(type="datetime")
     */
    private DateTimeInterface $createdAt;

    /**
     * @ORM\Column(type="boolean")
     */
    private bool $isPublished;

    /**
     * @ORM\ManyToOne(targetEntity=User::class, inversedBy="cheeseListings")
     * @ORM\JoinColumn(nullable=false)
     * @Assert\Valid()
     */
    #[Groups(['cheeses:read', 'cheeses:write'])]
    private $owner;

    public function __construct()
    {
        $this->createdAt = new DateTimeImmutable();
        $this->isPublished = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    /**
     * The description of the cheese as raw text.
     * @param string $description
     * @return $this
     */
    #[Groups(['cheeses:write', 'user:write'])]
    #[SerializedName('description')]
    public function setTextDescription(string $description): self
    {
        $this->description = str_replace(["\r\n", "\r", "\n"], "<br />", $description);
        return $this;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    /**
     * Get a part of description limited to 40 characters
     * @return string|null
     */
    #[Groups(['cheeses:read'])]
    public function getShortDescription(): ?string
    {
        if (strlen($this->getDescription()) < 40) {
            return strip_tags($this->getDescription());
        }

        return substr(strip_tags($this->getDescription()), 0, 40) . '...';
    }

    public function setPrice(int $price): self
    {
        $this->price = $price;
        return $this;
    }

    public function getPrice(): ?int
    {
        return $this->price;
    }

    public function getCreatedAt(): ?DateTimeInterface
    {
        return $this->createdAt;
    }

    /**
     * How long ago this cheese item was added in text format, example "1 day ago".
     * @return string
     */
    #[Groups(['cheeses:read'])]
    public function getCreatedAtAgo(): string
    {
        return Carbon::instance($this->getCreatedAt())->diffForHumans();
    }

    public function setIsPublished(bool $isPublished): self
    {
        $this->isPublished = $isPublished;
        return $this;
    }

    public function getIsPublished(): ?bool
    {
        return $this->isPublished;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;

        return $this;
    }
}