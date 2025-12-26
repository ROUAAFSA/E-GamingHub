<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use App\Entity\Utilisateur;

final class ProfileSettingsController extends AbstractController
{
    #[Route('/profile/settings', name: 'app_profile_settings')]
    public function index(): Response
    {
        // Récupérer l'utilisateur de la session
        $session = $this->container->get('request_stack')->getSession();
        $user = $session->get('user');
        
        if (!$user) {
            // Rediriger vers la page de connexion avec un message
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page');
            return $this->redirectToRoute('app_sign_in');
        }

        // Si l'utilisateur n'a pas de photo, utiliser l'image par défaut
        if (!isset($user['photo']) || empty($user['photo'])) {
            $user['photo'] = 'Sign_in/img/user.png';
            $session->set('user', $user);
        }

        return $this->render('profile_settings/index.html.twig', [
            'user' => $user
        ]);
    }

    #[Route('/profile/settings/update', name: 'app_profile_settings_update', methods: ['POST'])]
    public function update(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $session = $this->container->get('request_stack')->getSession();
        $userData = $session->get('user');
        
        if (!$userData) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page');
            return $this->redirectToRoute('app_sign_in');
        }

        // Récupérer l'entité Utilisateur de la base de données
        $user = $entityManager->getRepository(Utilisateur::class)->find($userData['id']);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_sign_in');
        }

        // Mise à jour des informations de base
        $user->setNom($request->request->get('nom'));
        $user->setPrenom($request->request->get('prenom'));
        $user->setEmail($request->request->get('email'));

        // Gestion du changement de mot de passe
        $currentPassword = $request->request->get('current_password');
        $newPassword = $request->request->get('new_password');
        $confirmPassword = $request->request->get('confirm_password');

        if ($currentPassword && $newPassword && $confirmPassword) {
            // Vérifier que le mot de passe actuel est correct
            if (!password_verify($currentPassword, $user->getPassword())) {
                $this->addFlash('error', 'Le mot de passe actuel est incorrect');
                return $this->redirectToRoute('app_profile_settings');
            }

            // Vérifier que les nouveaux mots de passe correspondent
            if ($newPassword !== $confirmPassword) {
                $this->addFlash('error', 'Les nouveaux mots de passe ne correspondent pas');
                return $this->redirectToRoute('app_profile_settings');
            }

            // Mettre à jour le mot de passe
            $user->setPassword(password_hash($newPassword, PASSWORD_BCRYPT));
            $this->addFlash('success', 'Mot de passe modifié avec succès');
        }

        // Gestion de l'upload de la photo
        $photoFile = $request->files->get('photo');
        if ($photoFile) {
            $originalFilename = pathinfo($photoFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$photoFile->guessExtension();

            try {
                $photoFile->move(
                    $this->getParameter('photos_directory'),
                    $newFilename
                );
                // Mettre à jour avec le chemin relatif pour l'affichage
                $user->setPhoto('uploads/photos/' . $newFilename);
                $userData['photo'] = 'uploads/photos/' . $newFilename;
            } catch (FileException $e) {
                $this->addFlash('error', 'Une erreur est survenue lors du téléchargement de la photo');
                // En cas d'erreur, conserver l'image par défaut
                if (!$user->getPhoto()) {
                    $user->setPhoto('Sign_in/img/user.png');
                    $userData['photo'] = 'Sign_in/img/user.png';
                }
            }
        } else if (!$user->getPhoto()) {
            // Si aucune nouvelle photo n'est uploadée et qu'il n'y a pas de photo existante
            $user->setPhoto('Sign_in/img/user.png');
            $userData['photo'] = 'Sign_in/img/user.png';
        }

        // Sauvegarde des modifications
        $entityManager->flush();

        // Mettre à jour les données de session
        $userData['nom'] = $user->getNom();
        $userData['prenom'] = $user->getPrenom();
        $userData['email'] = $user->getEmail();
        $session->set('user', $userData);

        $this->addFlash('success', 'Vos modifications ont été enregistrées avec succès');
        return $this->redirectToRoute('app_profile_settings');
    }

    #[Route('/profile/settings/delete', name: 'app_delete_account', methods: ['POST'])]
    public function deleteAccount(Request $request, EntityManagerInterface $entityManager): Response
    {
        $session = $this->container->get('request_stack')->getSession();
        $userData = $session->get('user');
        
        if (!$userData) {
            $this->addFlash('error', 'Veuillez vous connecter pour accéder à cette page');
            return $this->redirectToRoute('app_sign_in');
        }

        // Récupérer l'utilisateur
        $user = $entityManager->getRepository(Utilisateur::class)->find($userData['id']);
        
        if (!$user) {
            $this->addFlash('error', 'Utilisateur non trouvé');
            return $this->redirectToRoute('app_sign_in');
        }

        // Supprimer l'utilisateur
        $entityManager->remove($user);
        $entityManager->flush();

        // Supprimer la session
        $session->remove('user');

        $this->addFlash('success', 'Votre compte a été supprimé avec succès');
        return $this->redirectToRoute('app_home');
    }
}
