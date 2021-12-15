<?php

namespace App\Controller;

use App\Entity\Comentarios;
use App\Entity\Posts;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;

class DashboardController extends AbstractController
{
    /**
     * @Route("/", name="dashboard")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function index(PaginatorInterface $paginator,Request $request)
    {
      

        $user = $this->getUser();
        if($user){
  $em= $this->getDoctrine()->getManager();
        $query= $em->getRepository(Posts::class)->posts();
        $comentarios = $em->getRepository(Comentarios::class)->BuscarComentarios($user->getId()); 
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1),/*page number*/
            3/*limit per page*/
        );
        return $this->render('dashboard/index.html.twig', [
            'controller_name' => 'Bienvenido a clases ',
            'pagination'=>$pagination,
            'comentarios'=>$comentarios
            
        ]);
        }else{

            return $this->redirectToRoute('app_login');
        }

      
    }
}
