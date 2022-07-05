<?php

namespace App\Controller\Front\driver;

use App\Entity\DriverOrder;
use App\Repository\OrderRepository;
use App\Repository\CompanyRepository;
use App\Repository\DriverOrderRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

#[IsGranted("ROLE_DRIVER")]
#[Route("/driver/orders")]
class DriverController extends AbstractController
{
    /**
     * @IsGranted("ROLE_DRIVER")
    */
    
    #[Route('/', name: 'driver_orders')]
    public function index(OrderRepository $orderRepository, CompanyRepository $companyRepository): Response
    {
        $company = $companyRepository->find($this->getUser()->getCompany());

        $orders = $orderRepository->findAllOrderWhenTypeWhitStatus('DRIVER', 'DRIVER_NEW');

        $getDriverOrders = $company->getDriverOrders();

        $currentOrder = null;
        
        if(!empty($getDriverOrders)){
            foreach($getDriverOrders as $driverOrder){
                $driverOrders[] = $driverOrder->getCommand();
                foreach($driverOrders as $order){
                    if($order->getStatus() !== 'DRIVER_NEW' || $order->getStatus() !== 'DRIVER_CLOSE'){
                        $currentOrder = $order;
                    }
                }
            }
        }
        
        return $this->render('front/driver/orders/index.html.twig', [
            'controller_name' => 'DriverController',
            'orders' => $orders,
            'currentOrder' => $currentOrder,
        ]);
    }

    #[Route('/take-order/{order_id}', name: 'take_order')]
    public function takeOrder(OrderRepository $orderRepository, CompanyRepository $companyRepository,  $order_id): Response
    {
        $order = $orderRepository->find($order_id);
        $company = $companyRepository->find($this->getUser()->getCompany());

        $entityManager = $this->getDoctrine()->getManager();
        $order->setStatus('DRIVER_ACCEPT');
        $entityManager->persist($order);
        $entityManager->flush();

        $driverOrder = new DriverOrder();
        $driverOrder->setDriver($company);
        $driverOrder->setCommand($order);
        $entityManager->persist($driverOrder);
        $entityManager->flush();

        return $this->redirectToRoute('my_order', ['id' => $order->getId()]);
    }

    #[Route('/order_arrive_to_client/{order_id}', name: 'order_arrive_to_client')]
    public function arriveDriverOrder(OrderRepository $orderRepository,  $order_id): Response
    {

        $order = $orderRepository->find($order_id);

        $entityManager = $this->getDoctrine()->getManager();

        $order->setStatus('DRIVER_ARRIVES');

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->redirectToRoute('my_order', ['id' => $order->getId()]);
    }

    #[Route('/order_valid/{order_id}', name: 'order_valid')]
    public function validOrder(OrderRepository $orderRepository,  $order_id): Response
    {

        $order = $orderRepository->find($order_id);

        $entityManager = $this->getDoctrine()->getManager();

        $order->setStatus('DRIVER_PROCESSING_ACCEPT');

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->redirectToRoute('my_order', ['id' => $order->getId()]);
    }

    #[Route('/order_invalid/{order_id}', name: 'order_invalid')]
    public function deleteInvalid(OrderRepository $orderRepository,  $order_id): Response
    {

        $order = $orderRepository->find($order_id);
        $entityManager = $this->getDoctrine()->getManager();

        $order->setStatus('DRIVER_PROCESSING_REFUSED');
        $order->setDeletedAt(new \DateTime());

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->redirectToRoute('driver_orders');
    }

    #[Route('/order_stocked/{order_id}', name: 'order_stocked')]
    public function orderStocked(OrderRepository $orderRepository,  $order_id): Response
    {

        $order = $orderRepository->find($order_id);

        $entityManager = $this->getDoctrine()->getManager();

        $order->setStatus('DRIVER_STOCKED');

        $entityManager->persist($order);
        $entityManager->flush();

        return $this->redirectToRoute('driver_orders');
    }

    #[Route('/order/{id}', name: 'my_order')]
    public function myOrder(OrderRepository $orderRepository, CompanyRepository $companyRepository,  $id): Response
    {

        $order = $orderRepository->find($id);

        return $this->render('front/driver/orders/show.html.twig', [
            'order' => $order,
        ]);
    }
}
