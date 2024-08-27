<?php
// Déclaration du namespace pour organiser le code dans le répertoire Controller/Admin
namespace App\Controller\Admin;
// Importation des classes nécessaires pour les entités, formulaires, repository, et autres services
use App\Entity\Contact;
use App\Form\ContactType;
use App\Form\ResponseFormType;
use App\Repository\ContactRepository;
use Doctrine\ORM\EntityManagerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Attribute\Route;

// Définition de la route de base pour ce contrôleur. Toutes les routes dans cette classe commenceront par /admin/contact
#[Route('/admin/contact')]
class AdminContactController extends AbstractController
{
    // Route pour afficher la liste des contacts avec la méthode GET
    #[Route('/', name: 'app_admin_contact_index', methods: ['GET'])]
    public function index(Request $request, ContactRepository $contactRepository, PaginatorInterface $paginator): Response
    {
        // Création d'un QueryBuilder pour récupérer tous les contacts depuis la base de données
        $queryBuilder = $contactRepository->createQueryBuilder('c');

        // Pagination des résultats de la requête
        $pagination = $paginator->paginate(
            $queryBuilder, // La requête de données
            $request->query->getInt('page', 1), // Numéro de la page actuelle, par défaut 1 si non spécifié
            10 // Nombre d'éléments par page
        );

        // Rendu du template Twig avec les données de pagination
        return $this->render('admin/admin_contact/index.html.twig', [
            'pagination' => $pagination,
        ]);
    }

    // Route pour afficher et traiter le formulaire de réponse à un contact spécifique, avec les méthodes GET et POST
    #[Route('/{id}/response', name: 'app_admin_contact_response', methods: ['GET', 'POST'])]
    public function response(Request $request, Contact $contact, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        // Création du formulaire de réponse à partir du formulaire ResponseFormType
        $form = $this->createForm(ResponseFormType::class, $contact);
        // Traitement de la requête avec les données du formulaire
        $form->handleRequest($request);

        // Vérification si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrement des modifications dans la base de données
            $entityManager->flush();

            // Création d'un e-mail pour envoyer la réponse au contact
            $email = (new Email())
                ->from('admin@example.com') // Expéditeur de l'e-mail
                ->to($contact->getEmail()) // Destinataire de l'e-mail
                ->subject('Response to your message: ' . $contact->getTitle()) // Sujet de l'e-mail
                ->text('Hello,' . "\n\n" .
                       'Thank you for reaching out. Here is the response to your message:' . "\n\n" .
                       $contact->getResponse() . "\n\n" .
                       'Best regards,' . "\n" .
                       'The Team'); // Corps du message

            // Envoi de l'e-mail
            $mailer->send($email);

            // Ajout d'un message flash pour indiquer le succès de l'opération
            $this->addFlash('success', 'Your response has been sent.');

            // Redirection vers la liste des contacts
            return $this->redirectToRoute('app_admin_contact_index');
        }

        // Rendu du template Twig avec le formulaire de réponse
        return $this->render('admin/admin_contact/response.html.twig', [
            'contact' => $contact, // Données du contact
            'form' => $form->createView(), // Vue du formulaire
        ]);
    }

    // Route pour afficher le formulaire de création d'un nouveau contact avec les méthodes GET et POST
    #[Route('/new', name: 'app_admin_contact_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        // Création d'un nouvel objet Contact
        $contact = new Contact();
        // Création du formulaire pour le nouveau contact
        $form = $this->createForm(ContactType::class, $contact);
        // Traitement de la requête avec les données du formulaire
        $form->handleRequest($request);

        // Vérification si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrement du nouveau contact dans la base de données
            $entityManager->persist($contact);
            $entityManager->flush();

            // Redirection vers la liste des contacts après la création
            return $this->redirectToRoute('app_admin_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig avec le formulaire de création de contact
        return $this->render('admin/admin_contact/new.html.twig', [
            'contact' => $contact, // Données du contact
            'form' => $form, // Formulaire pour le nouveau contact
        ]);
    }

    // Route pour afficher les détails d'un contact spécifique avec la méthode GET
    #[Route('/{id}', name: 'app_admin_contact_show', methods: ['GET'])]
    public function show(Contact $contact): Response
    {
        // Rendu du template Twig pour afficher les détails du contact
        return $this->render('admin/admin_contact/show.html.twig', [
            'contact' => $contact, // Données du contact
        ]);
    }

    // Route pour afficher et traiter le formulaire d'édition d'un contact spécifique avec les méthodes GET et POST
    #[Route('/{id}/edit', name: 'app_admin_contact_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        // Création du formulaire pour l'édition du contact
        $form = $this->createForm(ContactType::class, $contact);
        // Traitement de la requête avec les données du formulaire
        $form->handleRequest($request);

        // Vérification si le formulaire est soumis et valide
        if ($form->isSubmitted() && $form->isValid()) {
            // Enregistrement des modifications dans la base de données
            $entityManager->flush();

            // Redirection vers la liste des contacts après l'édition
            return $this->redirectToRoute('app_admin_contact_index', [], Response::HTTP_SEE_OTHER);
        }

        // Rendu du template Twig avec le formulaire d'édition de contact
        return $this->render('admin/admin_contact/edit.html.twig', [
            'contact' => $contact, // Données du contact
            'form' => $form, // Formulaire pour l'édition du contact
        ]);
    }

    // Route pour supprimer un contact spécifique avec la méthode POST
    #[Route('/{id}', name: 'app_admin_contact_delete', methods: ['POST'])]
    public function delete(Request $request, Contact $contact, EntityManagerInterface $entityManager): Response
    {
        // Vérification de la validité du token CSRF pour la suppression
        if ($this->isCsrfTokenValid('delete'.$contact->getId(), $request->request->get('_token'))) {
            // Suppression du contact de la base de données
            $entityManager->remove($contact);
            $entityManager->flush();
        }

        // Redirection vers la liste des contacts après la suppression
        return $this->redirectToRoute('app_admin_contact_index', [], Response::HTTP_SEE_OTHER);
    }
}

