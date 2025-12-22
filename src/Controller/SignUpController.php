<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class SignUpController extends AbstractController
{
    public function signUp(Request $request, ValidatorInterface $validator): Response
    {
        $errorArray = [];

        if ($request->isMethod('POST')) {
            $formData = $request->request->all();

            // Here you would typically create and validate your User entity
            // For now, we'll simulate some validation errors
            $errors = []; // This would come from $validator->validate($user);

            // Example validation error simulation
            if (empty($formData['email'])) {
                $errors[] = new class {
                    public function getPropertyPath() { return 'email'; }
                    public function getMessage() { return 'Veuillez entrer votre adresse email.'; }
                };
            }

            // Conversion des erreurs de validation en tableau
            foreach ($errors as $error) {
                $propertyPath = $error->getPropertyPath();
                // Conversion des noms de propriétés pour correspondre aux data-error
                switch ($propertyPath) {
                    case 'password':
                        $errorArray['password'] = 'Veuillez entrer un mot de passe d\'au moins 6 caractères.';
                        break;
                    case 'email':
                        $errorArray['email'] = 'Veuillez entrer votre adresse email.';
                        break;
                    case 'nom':
                        $errorArray['nom'] = 'Veuillez entrer votre nom.';
                        break;
                    case 'prenom':
                        $errorArray['prenom'] = 'Veuillez entrer votre prénom.';
                        break;
                    case 'role':
                        $errorArray['role'] = 'Veuillez sélectionner votre rôle dans la liste.';
                        break;
                    default:
                        $errorArray[$propertyPath] = $error->getMessage();
                }
            }
        }

        return $this->render('sign_up/index.html.twig', [
            'errors' => $errorArray
        ]);
    }
}
