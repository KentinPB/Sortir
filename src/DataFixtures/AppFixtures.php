<?php

namespace App\DataFixtures;

use App\Entity\Campus;
use App\Entity\Etat;
use App\Entity\Lieu;
use App\Entity\Participant;
use App\Entity\Sortie;
use App\Entity\Ville;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class AppFixtures extends Fixture
{
    public function __construct(
        private UserPasswordHasherInterface $passwordHasher
    ) {
    }

    public function load(ObjectManager $manager): void
    {
        // 1. Création des États
        $etatsNoms = ['En création', 'Ouverte', 'Clôturée', 'En cours', 'Terminée', 'Annulée', 'Historisée'];
        $etats = [];
        foreach ($etatsNoms as $nom) {
            $etat = new Etat();
            $etat->setLibelle($nom);
            $manager->persist($etat);
            $etats[$nom] = $etat;
        }

        // 2. Création des Campus
        $campusNoms = ['SAINT-HERBLAIN', 'CHARTRES-DE-BRETAGNE', 'LA ROCHE-SUR-YON'];
        $campusList = [];
        foreach ($campusNoms as $nom) {
            $campus = new Campus();
            $campus->setNom($nom);
            $manager->persist($campus);
            $campusList[$nom] = $campus;
        }

        // 3. Création des Villes
        $villesData = [
            ['SAINT-HERBLAIN', '44800'],
            ['RENNES', '35000'],
            ['FOUGERES', '35300'],
            ['NANTES', '44000'],
            ['VANNES', '56000'],
            ['BREST', '29200'],
        ];
        $villes = [];
        foreach ($villesData as $data) {
            $ville = new Ville();
            $ville->setNom($data[0]);
            $ville->setCodePostal($data[1]);
            $manager->persist($ville);
            $villes[$data[0]] = $ville;
        }

        // 4. Création des Lieux
        $lieuxData = [
            ['Cinéma Gaumont', 'Esplanade Charles de Gaulle', 48.1054, -1.6736, 'RENNES'],
            ['Restaurant Le Bouffay', '12 Rue de la Paix', 47.2184, -1.5536, 'NANTES'],
            ['Bowling de Saint-Herblain', 'Z.I. du Vendômois', 47.2275, -1.6385, 'SAINT-HERBLAIN'],
            ['Escape Game Illucio', '5 Rue Nationale', 47.6582, -2.7608, 'VANNES'],
            ['Bar Le Terminus', '14 Avenue de la Gare', 48.3904, -4.4861, 'BREST'],
            ['Parc de la Piverdière', 'Route de Châtillon', 48.0812, -1.6502, 'RENNES'],
            ['Centre Culturel Yves Montand', 'Place de l\'Hôtel de Ville', 48.3551, -1.2078, 'FOUGERES'],
        ];

        $lieux = [];
        foreach ($lieuxData as $data) {
            $lieu = new Lieu();
            $lieu->setNom($data[0]);
            $lieu->setRue($data[1]);
            $lieu->setLatitude($data[2]);
            $lieu->setLongitude($data[3]);
            $lieu->setVille($villes[$data[4]]);
            $manager->persist($lieu);
            $lieux[$data[0]] = $lieu;
        }

        // 5. Création des Participants
        $participantsData = [
            ['Jeannine', 'L.', 'Jeannine L.', 'jeannine@sortir.com', true, 'CHARTRES-DE-BRETAGNE'],
            ['Rémi', 'S.', 'Rémi S.', 'remi@sortir.com', false, 'CHARTRES-DE-BRETAGNE'],
            ['Spinoza', 'A.', 'Spinoz A.', 'spinoza@sortir.com', false, 'SAINT-HERBLAIN'],
            ['Jojo', '56', 'Jojo56', 'jojo@sortir.com', false, 'LA ROCHE-SUR-YON'],
            ['Raymond', 'D.', 'Raymond D.', 'raymond@sortir.com', false, 'CHARTRES-DE-BRETAGNE'],
            ['Alice', 'M.', 'AliceM', 'alice@sortir.com', false, 'SAINT-HERBLAIN'],
            ['Marc', 'B.', 'MarcB', 'marc@sortir.com', true, 'LA ROCHE-SUR-YON'],
            ['Sophie', 'T.', 'SophieT', 'sophie@sortir.com', false, 'CHARTRES-DE-BRETAGNE'],
        ];

        $participants = [];
        foreach ($participantsData as $data) {
            $participant = new Participant();
            $participant->setPrenom($data[0]);
            $participant->setNom($data[1]);
            $participant->setPseudo($data[2]);
            $participant->setEmail($data[3]);
            $participant->setAdministrateur($data[4]);
            $participant->setActif(true);
            $participant->setTelephone('06' . random_int(10, 99) . random_int(10, 99) . random_int(10, 99) . random_int(10, 99));
            $participant->setRoles($data[4] ? ['ROLE_ADMIN'] : ['ROLE_USER']);
            $participant->setCampus($campusList[$data[5]]);

            $hashedPassword = $this->passwordHasher->hashPassword($participant, 'password123');
            $participant->setPassword($hashedPassword);

            $manager->persist($participant);
            $participants[$data[2]] = $participant; // Index par pseudo
        }

        // 6. Création des Sorties
        // Format : [Nom, Etat, Organisateur, Lieu, NbMax, DateDébut (Modificateur), Durée (minutes), Description]
        $sortiesData = [
            ['Philo', 'En cours', 'Spinoz A.', 'Cinéma Gaumont', 8, '+2 hours', 120, 'Atelier de discussion philosophique ouvert à tous.'],
            ['Origamie', 'Clôturée', 'Rémi S.', 'Parc de la Piverdière', 5, '+1 day', 90, 'Initiation aux techniques de pliage de papier en extérieur.'],
            ['Perles', 'Clôturée', 'Jojo56', 'Restaurant Le Bouffay', 12, '+2 days', 180, 'Création de bijoux artisanaux en perles de verre.'],
            ['Concert métal', 'Ouverte', 'Raymond D.', 'Bar Le Terminus', 10, '+1 week', 240, 'Soirée concert rock et heavy metal live.'],
            ['Jardinage urbain', 'Ouverte', 'Rémi S.', 'Parc de la Piverdière', 5, '+3 days', 150, 'Apprendre à cultiver sur un balcon ou en ville.'],
            ['Séance Ciné', 'En création', 'Jeannine L.', 'Cinéma Gaumont', 10, '+2 weeks', 120, 'Visionnage du dernier film à l\'affiche et débat.'],
            ['Pâte à sel', 'Ouverte', 'Jeannine L.', 'Centre Culturel Yves Montand', 5, '+10 days', 90, 'Activité créative manuelle accessible à tous.'],
            ['Laser Game', 'Ouverte', 'AliceM', 'Bowling de Saint-Herblain', 12, '+4 days', 90, 'Session endiablée de laser game en équipe.'],
            ['Randonnée côtière', 'Ouverte', 'MarcB', 'Bar Le Terminus', 15, '+5 days', 300, 'Grande randonnée le long de la côte bretonne.'],
            ['Tournoi de Bowling', 'Clôturée', 'SophieT', 'Bowling de Saint-Herblain', 8, '-2 days', 120, 'Compétition amicale de bowling entre membres.'],
            ['Atelier Cuisine du Monde', 'Terminée', 'Spinoz A.', 'Restaurant Le Bouffay', 10, '-1 week', 180, 'Partage de recettes et dégustation de plats internationaux.'],
            ['Sortie Escape Game', 'Annulée', 'Jojo56', 'Escape Game Illucio', 6, '-3 days', 90, 'Résolution d\'énigmes en temps limité (Annulé suite météo).'],
        ];

        foreach ($sortiesData as $data) {
            $sortie = new Sortie();
            $sortie->setNom($data[0]);
            $sortie->setEtat($etats[$data[1]]);
            $sortie->setOrganisateur($participants[$data[2]]);
            $sortie->setSiteOrganisateur($participants[$data[2]]->getCampus());
            $sortie->setLieu($lieux[$data[3]]);
            $sortie->setNbInscriptionsMax($data[4]);

            $dateDebut = new \DateTime($data[5]);
            $sortie->setDateHeureDebut($dateDebut);
            $sortie->setDuree($data[6]);

            $dateLimite = clone $dateDebut;
            $dateLimite->modify('-2 days');
            $sortie->setDateLimiteInscription($dateLimite);

            $sortie->setInfoSortie($data[7]);

            // Gestion du motif d'annulation (obligatoire si le champ est NOT NULL en base)
            $sortie->setMotifAnnulation($data[1] === 'Annulée' ? 'Annulation pour raisons climatiques exceptionnelles.' : '');

            // Ajout de quelques participants inscrits si la sortie n'est pas "En création"
            if ($data[1] !== 'En création') {
                $sortie->addParticipant($participants['Jeannine L.']);
                $sortie->addParticipant($participants['Rémi S.']);
                if ($data[4] > 3) {
                    $sortie->addParticipant($participants['Spinoz A.']);
                }
            }

            $manager->persist($sortie);
        }

        $manager->flush();
    }
}
