<?php

namespace App\Controller\Front\user;


use App\Entity\CreditCard;
use App\Entity\User;
use App\Entity\Media;
use App\Form\NewCreditCardFormType;
use App\Form\UserAccountUpdateFormType;
use App\Repository\CreditCardRepository;
use App\Repository\UserRepository;
use phpDocumentor\Reflection\Types\Integer;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;


#[Route('/paramètres')]
class UserController extends AbstractController
{
    /**
     * @IsGranted("ROLE_USER")
     */

    #[Route('/compte', name: 'app_settings_account')]
    public function account(Request $request, UserRepository $userRep): Response
    {
        $user = $userRep->find($this->getUser());
        $form = $this->createForm(UserAccountUpdateFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->getDoctrine()->getManager()->flush();
            $this->addFlash(
                'success',
                'Vos données ont bien été changées'
            );

            return $this->redirectToRoute('app_settings_account');
        }
        return $this->render('front/user/settings/account.html.twig', [
            'userAccountUpdateForm' => $form->createView(),
            'user' => $user,
        ]);
    }
    /**  ********************* */
    /**
     * @IsGranted("ROLE_USER")
     */
    #[Route('/portefeuille', name: 'app_settings_wallet')]
    public function wallet(CreditCardRepository $creditCardRep): Response
    {
        $user = $this->getUser();
        $wallets = $creditCardRep->findBy(['possessor' => $user]);

        foreach ($wallets as $wallet) {
            $numbs = str_split($wallet->getNumber(), 4);
            $wallet->setNumber($numbs[3]);
        }

        if (count($wallets) >= 5) {
            $this->addFlash(
                'error',
                'Vous avez atteint votre limite de carte'
            );
        }
        return $this->render('front/user/settings/wallet.html.twig', [
            'controller_name' => 'UserController',
            'wallets' => $wallets,
        ]);
    }

    #[Route('/portefeuille/nouvelle-carte', name: 'app_settings_wallet_new')]
    public function newCard(Request $request, CreditCardRepository $creditCardRep): Response
    {
        $card = new CreditCard();
        $form = $this->createForm(NewCreditCardFormType::class, $card);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $card->setPossessor($this->getUser());
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($card);
            $entityManager->flush();
            $this->addFlash(
                'success',
                'Votre carte a bien été rajoutée'
            );
            return $this->redirectToRoute('app_settings_wallet');
        }

        return $this->render('front/user/settings/newCard.html.twig', [
            'NewCreditCardForm' => $form->createView(),
        ]);
    }


    #[Route('/portefeuille/supprimer-carte/{id}', name: 'app_settings_wallet_delete')]
    public function delete(Request $request, CreditCard $creditCardRep): Response
    {
        $em = $this->getDoctrine()->getManager();
        $em->remove($creditCardRep);
        $em->flush();

        return $this->redirectToRoute('app_settings_wallet');
    }


    /**  ********************* */

    #[Route('/mot-de-passe', name: 'app_settings_password')]
    public function password(Request $request): Response
    {
        $user = $this->getUser();
        $form = $this->createForm(UserAccountUpdateFormType::class, $user);
        $form->handleRequest($request);

        return $this->render('front/user/settings/password.html.twig', [
            'ChangeUserPasswordForm' => $form->createView(),
        ]);
    }
}
