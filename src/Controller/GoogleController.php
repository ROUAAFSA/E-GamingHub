<?php

namespace App\Controller;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use League\OAuth2\Client\Provider\Google;
use League\OAuth2\Client\Provider\Exception\IdentityProviderException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class GoogleController extends AbstractController
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    #[Route('/connect/google', name: 'connect_google')]
    public function connectAction(): Response
    {
        // Générer l'URL de redirection absolue
        $redirectUri = $this->generateUrl('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // Afficher l'URL pour le débogage
        dump('URL de redirection : ' . $redirectUri);
        
        $provider = new Google([
            'clientId'     => $this->getParameter('app.google_client_id'),
            'clientSecret' => $this->getParameter('app.google_client_secret'),
            'redirectUri'  => $redirectUri,
            'scopes'       => ['email', 'profile'],
        ]);

        $authUrl = $provider->getAuthorizationUrl();
        $this->requestStack->getSession()->set('oauth2state', $provider->getState());

        return $this->redirect($authUrl);
    }

    #[Route('/connect/google/check', name: 'connect_google_check')]
    public function connectCheckAction(Request $request): Response
    {
        if (!$request->query->get('state') || 
            $request->query->get('state') !== $this->requestStack->getSession()->get('oauth2state')) {
            $this->requestStack->getSession()->remove('oauth2state');
            return $this->redirectToRoute('app_sign_in');
        }

        // Générer l'URL de redirection absolue
        $redirectUri = $this->generateUrl('connect_google_check', [], UrlGeneratorInterface::ABSOLUTE_URL);
        
        // Afficher l'URL pour le débogage
        dump('URL de redirection check : ' . $redirectUri);

        $provider = new Google([
            'clientId'     => $this->getParameter('app.google_client_id'),
            'clientSecret' => $this->getParameter('app.google_client_secret'),
            'redirectUri'  => $redirectUri,
        ]);

        try {
            // Obtenir le token d'accès
            $token = $provider->getAccessToken('authorization_code', [
                'code' => $request->query->get('code')
            ]);

            // Obtenir les données de l'utilisateur
            $user = $provider->getResourceOwner($token);
            
            // Chercher si l'utilisateur existe déjà
            $existingUser = $this->entityManager->getRepository(Utilisateur::class)
                ->findOneBy(['email' => $user->getEmail()]);

            if (!$existingUser) {
                // Créer un nouvel utilisateur
                $newUser = new Utilisateur();
                $newUser->setEmail($user->getEmail());
                $newUser->setNom($user->getLastName() ?? 'Unknown');
                $newUser->setPrenom($user->getFirstName() ?? 'Unknown');
                $newUser->setPassword(''); // Pas de mot de passe pour les utilisateurs Google
                $newUser->setRole('joueur'); // Changé de 'user' à 'joueur' pour correspondre à vos rôles
                $newUser->setPhoto($user->getAvatar() ?? 'Sign_in/img/user.png');
                $newUser->setIsBlocked(false);

                $this->entityManager->persist($newUser);
                $this->entityManager->flush();

                $existingUser = $newUser;
            }

            // Connecter l'utilisateur
            $session = $this->requestStack->getSession();
            $session->set('user', [
                'id' => $existingUser->getId(),
                'email' => $existingUser->getEmail(),
                'nom' => $existingUser->getNom(),
                'prenom' => $existingUser->getPrenom(),
                'role' => $existingUser->getRole(),
                'photo' => $existingUser->getPhoto()
            ]);

            return $this->redirectToRoute($existingUser->getRole() === 'admin' ? 'app_dashboard_admin' : 'app_home');

        } catch (IdentityProviderException $e) {
            // En cas d'erreur, afficher les détails pour le débogage
            dump('Erreur OAuth : ' . $e->getMessage());
            $this->addFlash('error', 'Une erreur est survenue lors de la connexion avec Google');
            return $this->redirectToRoute('app_sign_in');
        }
    }
} 