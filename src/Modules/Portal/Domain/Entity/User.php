<?php

declare(strict_types=1);

namespace App\Modules\Portal\Domain\Entity;

use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[ORM\Table(name: 'portal__users')]
#[UniqueEntity(fields: ['email'], message: 'Il existe déjà un compte avec cet email')]
#[ORM\UniqueConstraint(name: 'user_username', columns: ['username'])]
#[ORM\UniqueConstraint(name: 'user_email', columns: ['email'])]
#[ORM\HasLifecycleCallbacks]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
	#[ORM\Id]
	#[ORM\GeneratedValue]
	#[ORM\Column]
	private ?int $id = null;

	#[ORM\Column(length: 50)]
	#[Assert\NotBlank(message: 'Veuillez entrer votre identifiant')]
	#[Assert\Length(
		min: 2,
		max: 50,
		minMessage: 'L\'identifiant doit faire au moins {{ limit }} caractères',
		maxMessage: 'L\'identifiant ne peut pas dépasser {{ limit }} caractères'
	)]
	private ?string $username = null;

	#[ORM\Column(length: 180, unique: true)]
	#[Assert\NotBlank(message: 'Veuillez entrer une adresse email')]
	#[Assert\Email(message: 'L\'adresse email n\'est pas valide')]
	private ?string $email = null;

	#[ORM\Column]
	private array $roles = [];

	#[ORM\Column]
	#[Assert\NotBlank(message: 'Veuillez entrer un mot de passe')]
	#[Assert\Length(
		min: 8,
		minMessage: 'Le mot de passe doit faire au moins {{ limit }} caractères'
	)]
	private ?string $password = null;
	#[ORM\Column(type: 'datetime_immutable', nullable: true)]
	private ?\DateTimeImmutable $createdAt = null;
	#[ORM\Column(type: 'datetime_immutable', nullable: true)]
	private ?\DateTimeImmutable $updatedAt = null;
	#[ORM\Column(type: 'datetime_immutable', nullable: true)]
	private ?\DateTimeImmutable $lastLoggedAt = null;

	#[ORM\PrePersist]
	public function prePersist(): void
	{
		$this->createdAt = $this->updatedAt = new \DateTimeImmutable();
	}

	#[ORM\PreUpdate]
	public function preUpdate(): void
	{
		$this->updatedAt = new \DateTimeImmutable();
	}

	public function getId(): ?int
	{
		return $this->id;
	}

	public function setId(?int $id): static
	{
		$this->id = $id;
		return $this;
	}

	public function getUsername(): ?string
	{
		return $this->username;
	}

	public function setUsername(?string $username): static
	{
		$this->username = $username;

		return $this;
	}

	public function getEmail(): ?string
	{
		return $this->email;
	}

	public function setEmail(?string $email): static
	{
		$this->email = $email;

		return $this;
	}

	public function getUserIdentifier(): string
	{
		return (string) $this->username;
	}

	public function getRoles(): array
	{
		$roles = $this->roles;
		$roles[] = 'ROLE_USER';

		return array_unique($roles);
	}

	/**
	 * @param lisrt<string> $roles
	 */
	public function setRoles(array $roles): static
	{
		$this->roles = $roles;

		return $this;
	}

	/**
	 * @see PasswordAuthenticatedUserInterface
	 */
	public function getPassword(): string
	{
		return $this->password;
	}

	public function setPassword(string $password): static
	{
		$this->password = $password;

		return $this;
	}

	/**
	 * @see UserInterface
	 */
	public function eraseCredentials(): void
	{
		// Si vous stockez des données temporaires sensibles sur l'utilisateur, effacez-les ici
	}

	public function getCreatedAt(): ?\DateTimeImmutable
	{
		return $this->createdAt;
	}

	public function setCreatedAt(?\DateTimeImmutable $createdAt): static
	{
		$this->createdAt = $createdAt;

		return $this;
	}

	public function getUpdatedAt(): ?\DateTimeImmutable
	{
		return $this->updatedAt;
	}

	public function setUpdatedAt(?\DateTimeImmutable $updatedAt): static
	{
		$this->updatedAt = $updatedAt;

		return $this;
	}

	public function getLastLoggedAt(): ?\DateTimeImmutable
	{
		return $this->lastLoggedAt;
	}

	public function setLastLoggedAt(?\DateTimeImmutable $lastLoggedAt): static
	{
		$this->lastLoggedAt = $lastLoggedAt;

		return $this;
	}
}
