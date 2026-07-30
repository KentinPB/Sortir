<?php

namespace App\Entity;

use App\Repository\SortieRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

// 1. N'OUBLIEZ PAS CET IMPORT

#[ORM\Entity(repositoryClass: SortieRepository::class)]
class Sortie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank(message: 'Le nom de la sortie est obligatoire.')]
    #[Assert\Length(
        min: 3,
        max: 255,
        minMessage: 'Le nom doit contenir au moins {{ limit }} caractères.',
        maxMessage: 'Le nom ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $nom = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date et l\'heure de début sont obligatoires.')]
    #[Assert\GreaterThan(
        'now',
        message: 'La date de la sortie doit être située dans le futur.'
    )]
    private ?\DateTime $dateHeureDebut = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La durée est obligatoire.')]
    #[Assert\Positive(message: 'La durée doit être un nombre positif de minutes.')]
    private ?int $duree = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'La date limite d\'inscription est obligatoire.')]
    #[Assert\GreaterThan(
        'now',
        message: 'La date de clôture doit être dans le futur.'
    )]
    #[Assert\LessThan(
        propertyPath: 'dateHeureDebut',
        message: 'La date limite d\'inscription doit être antérieure à la date de la sortie.'
    )]
    private ?\DateTime $dateLimiteInscription = null;

    #[ORM\Column]
    #[Assert\NotBlank(message: 'Le nombre de places est obligatoire.')]
    #[Assert\Positive(message: 'Le nombre de places doit être supérieur à zero.')]
    private ?int $nbInscriptionsMax = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    #[Assert\Length(
        max: 2000,
        maxMessage: 'La description ne peut pas dépasser {{ limit }} caractères.'
    )]
    private ?string $infoSortie = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $motifAnnulation = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Etat $etat = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Campus $siteOrganisateur = null;

    #[ORM\ManyToOne(inversedBy: 'sortiesOrganisees')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Participant $organisateur = null;

    #[ORM\ManyToOne(inversedBy: 'sorties')]
    #[ORM\JoinColumn(nullable: false)]
    #[Assert\NotNull(message: 'Veuillez sélectionner un lieu.')]
    private ?Lieu $lieu = null;

    /**
     * @var Collection<int, Participant>
     */
    #[ORM\ManyToMany(targetEntity: Participant::class, inversedBy: 'sorties')]
    private Collection $participants;

    public function __construct()
    {
        $this->participants = new ArrayCollection();
        $this->motifAnnulation = ''; // Valeur par défaut pour éviter l'erreur NOT NULL
    }

    /**
     * 1. Vérifie si l'utilisateur passé en paramètre est l'organisateur de la sortie.
     */
    public function isOrganisateur(?Participant $user): bool
    {
        if ($user === null || $this->getOrganisateur() === null) {
            return false;
        }

        // On compare soit les objets directement, soit leurs ID
        return $this->getOrganisateur()->getId() === $user->getId();
    }

    /**
     * 2. Vérifie si la sortie est publiée/ouverte ou clôturée
     */
    public function isPubliee(): bool
    {
        return in_array($this->getEtat()?->getLibelle(), [Etat::OUVERTE, Etat::CLOTUREE], true);
    }

    /**
     * 3. Vérifie si la sortie est encore en création (brouillon)
     */
    public function isCreee(): bool
    {
        return $this->getEtat()?->getLibelle() === Etat::CREEE;
    }

    /**
     * 4. Vérifie si la sortie n'a pas encore commencé
     */
    public function isNonCommencee(): bool
    {
        return $this->getDateHeureDebut() > new \DateTime();
    }

    /**
     * 5. Méthode métier : Annulation d'une sortie
     */
    public function isAnnulableBy(?Participant $user): bool
    {
        // Utilisation directe des briques de base et méthodes métier
        return $this->isOrganisateur($user)
            && $this->isPubliee()
            && $this->isNonCommencee();
    }

    /**
     * 6. Méthode métier : Modification d'une sortie
     */
    public function isModifiableBy(?Participant $user): bool
    {
        return $this->isOrganisateur($user)
            && $this->isCreee();
    }

    /**
     * 7. Vérifie si la sortie est supprimable (non publiée + organisateur)
     */
    public function isSupprimableBy(?Participant $user): bool
    {
        // Réutilise la brique de création et la vérification d'organisateur
        return $this->isOrganisateur($user) && $this->isCreee();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDateHeureDebut(): ?\DateTime
    {
        return $this->dateHeureDebut;
    }

    public function setDateHeureDebut(\DateTime $dateHeureDebut): static
    {
        $this->dateHeureDebut = $dateHeureDebut;

        return $this;
    }

    public function getDuree(): ?int
    {
        return $this->duree;
    }

    public function setDuree(int $duree): static
    {
        $this->duree = $duree;

        return $this;
    }

    public function getDateLimiteInscription(): ?\DateTime
    {
        return $this->dateLimiteInscription;
    }

    public function setDateLimiteInscription(\DateTime $dateLimiteInscription): static
    {
        $this->dateLimiteInscription = $dateLimiteInscription;

        return $this;
    }

    public function getNbInscriptionsMax(): ?int
    {
        return $this->nbInscriptionsMax;
    }

    public function setNbInscriptionsMax(int $nbInscriptionsMax): static
    {
        $this->nbInscriptionsMax = $nbInscriptionsMax;

        return $this;
    }

    public function getInfoSortie(): ?string
    {
        return $this->infoSortie;
    }

    public function setInfoSortie(?string $infoSortie): static
    {
        $this->infoSortie = $infoSortie;

        return $this;
    }

    public function getMotifAnnulation(): ?string
    {
        return $this->motifAnnulation;
    }

    public function setMotifAnnulation(string $motifAnnulation): static
    {
        $this->motifAnnulation = $motifAnnulation;

        return $this;
    }

    public function getEtat(): ?Etat
    {
        return $this->etat;
    }

    public function setEtat(?Etat $etat): static
    {
        $this->etat = $etat;

        return $this;
    }

    public function getSiteOrganisateur(): ?Campus
    {
        return $this->siteOrganisateur;
    }

    public function setSiteOrganisateur(?Campus $siteOrganisateur): static
    {
        $this->siteOrganisateur = $siteOrganisateur;

        return $this;
    }

    public function getOrganisateur(): ?Participant
    {
        return $this->organisateur;
    }

    public function setOrganisateur(?Participant $organisateur): static
    {
        $this->organisateur = $organisateur;

        return $this;
    }

    public function getLieu(): ?Lieu
    {
        return $this->lieu;
    }

    public function setLieu(?Lieu $lieu): static
    {
        $this->lieu = $lieu;

        return $this;
    }

    /**
     * @return Collection<int, Participant>
     */
    public function getParticipants(): Collection
    {
        return $this->participants;
    }

    public function addParticipant(Participant $participant): static
    {
        if (!$this->participants->contains($participant)) {
            $this->participants->add($participant);
        }

        return $this;
    }

    public function removeParticipant(Participant $participant): static
    {
        $this->participants->removeElement($participant);

        return $this;
    }
}
