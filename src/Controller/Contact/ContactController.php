<?php
// Déclaration du namespace pour organiser le code. Ici, il est utilisé pour le contrôleur de contact dans l'application.
namespace App\Controller\Contact;

// Importation des classes nécessaires
use App\Entity\Contact; // Importation de l'entité Contact pour les messages de contact
use App\Form\ContactFormType; // Importation du formulaire ContactFormType pour gérer les messages de contact
use Doctrine\ORM\EntityManagerInterface; // Importation de l'interface EntityManager pour les opérations sur la base de données
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController; // Importation de la classe de base pour les contrôleurs Symfony
use Symfony\Component\HttpFoundation\Request; // Importation de la classe Request pour gérer les requêtes HTTP
use Symfony\Component\HttpFoundation\Response; // Importation de la classe Response pour gérer les réponses HTTP
use Symfony\Component\Routing\Annotation\Route; // Importation de l'annotation Route pour définir les routes

// Définition du contrôleur de contact
class ContactController extends AbstractController
{
    // Route pour afficher et traiter le formulaire de contact
    #[Route('/contact', name: 'contact')]
    public function contact(Request $request, EntityManagerInterface $entityManager): Response
    {
        $contact = new Contact(); // Création d'une nouvelle instance de l'entité Contact
        //$contact->setCreatedAt(new \DateTimeImmutable()); // Commenté : peut être utilisé pour définir une date de création automatique

        // Création du formulaire basé sur le type ContactFormType
        $form = $this->createForm(ContactFormType::class, $contact);

        // Gestion de la requête HTTP avec le formulaire
        $form->handleRequest($request);

        // Vérification si le formulaire a été soumis et est valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Définition de la date du message automatiquement si elle n'est pas déjà définie
            if (!$contact->getMsgDate()) {
                $contact->setMsgDate(new \DateTime()); // Définition de la date et heure actuelles
            }

            $entityManager->persist($contact); // Préparation de l'entité pour la persistance dans la base de données
            $entityManager->flush(); // Enregistrement des modifications dans la base de données

            $this->addFlash('success', 'Your message has been sent.'); // Ajout d'un message flash pour indiquer le succès de l'envoi

            return $this->redirectToRoute('contact'); // Redirection vers la même route après l'envoi du message
        }

        // Rendu du template Twig avec le formulaire
        return $this->render('contact/index.html.twig', [
            'form' => $form->createView(), // Passage de la vue du formulaire au template
        ]);
    }
}
