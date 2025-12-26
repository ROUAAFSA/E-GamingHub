<?php

namespace App\EventSubscriber;

use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\HttpFoundation\RequestStack;

class RememberMeSubscriber implements EventSubscriberInterface
{
    private $entityManager;
    private $requestStack;

    public function __construct(EntityManagerInterface $entityManager, RequestStack $requestStack)
    {
        $this->entityManager = $entityManager;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 35], // Priorité plus haute que le firewall
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        $request = $event->getRequest();
        $session = $this->requestStack->getSession();

        // Si l'utilisateur est déjà connecté, ne rien faire
        if ($session->has('user')) {
            return;
        }

        // Vérifier si le cookie remember_me existe
        $rememberMeToken = $request->cookies->get('remember_me');
        if (!$rememberMeToken) {
            return;
        }

        // Chercher un utilisateur avec ce token
        $user = $this->entityManager->getRepository(Utilisateur::class)
            ->findOneBy(['rememberMeToken' => $rememberMeToken]);

        if (!$user || $user->isBlocked()) {
            // Si le token n'est pas valide ou l'utilisateur est bloqué, supprimer le cookie
            $request->cookies->remove('remember_me');
            return;
        }

        // Connecter l'utilisateur
        $session->set('user', [
            'id' => $user->getId(),
            'email' => $user->getEmail(),
            'nom' => $user->getNom(),
            'prenom' => $user->getPrenom(),
            'role' => $user->getRole(),
            'photo' => $user->getPhoto()
        ]);
    }
} 