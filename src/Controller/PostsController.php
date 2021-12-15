<?php

namespace App\Controller;

use App\Entity\Comentarios;
use App\Entity\Posts;
use App\Form\ComentarioType;
use App\Form\PostsType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Symfony\Component\HttpFoundation\Response;


class PostsController extends AbstractController
{
    /**
     * @Route("/registrar-posts", name="RegistrarPosts")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function index(Request $request,SluggerInterface $slugger)
    {
        $posts=new Posts();
        $form= $this->createForm(PostsType::class,$posts);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){


            $brochureFile = $form->get('foto')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
               
                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('fotos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw  new \Exception('Error al subir archivos');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $posts->setFoto($newFilename);
            }




            $user= $this->getUser();
            $posts->setUser($user);
            $em= $this->getDoctrine()->getManager();
            $em->persist($posts);
            $em->flush();
            return $this->redirectToRoute('dashboard');
        }
        return $this->render('posts/index.html.twig', [
            'formulario' => $form->createView(),
        ]);
    }

    /**
     * @Route("/edit-posts/{id}", name="editar_posts",  methods={"GET", "POST"})
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function edit_post(Request $request,SluggerInterface $slugger,Posts $posts):Response
    {
      
        $form= $this->createForm(PostsType::class,$posts);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){


            $brochureFile = $form->get('foto')->getData();

            // this condition is needed because the 'brochure' field is not required
            // so the PDF file must be processed only when a file is uploaded
            if ($brochureFile) {
                $originalFilename = pathinfo($brochureFile->getClientOriginalName(), PATHINFO_FILENAME);
                // this is needed to safely include the file name as part of the URL
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$brochureFile->guessExtension();
               
                // Move the file to the directory where brochures are stored
                try {
                    $brochureFile->move(
                        $this->getParameter('fotos_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    throw  new \Exception('Error al subir archivos');
                }

                // updates the 'brochureFilename' property to store the PDF file name
                // instead of its contents
                $posts->setFoto($newFilename);
            }

            $em= $this->getDoctrine()->getManager();
            $em->flush();
            return $this->redirectToRoute('MisPosts');
        }
        return $this->render('posts/edit_posts.html.twig', [
            'posts'=>$posts,
            'formulario' => $form->createView(),
        ]);
    }




     /**
     * @Route("/posts/{id}", name="VerPosts")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
    public function VerPosts($id, Request $request, PaginatorInterface $paginator){

        $em=$this->getDoctrine()->getManager();
        $comentario= new Comentarios();
        $posts= $em->getRepository(Posts::class)->find($id);
        $queryComentarios = $em->getRepository(Comentarios::class)->BuscarComentariosDeUNPost($posts->getId());
        $form = $this->createForm(ComentarioType::class, $comentario);
        $form->handleRequest($request);
        if($form->isSubmitted() && $form->isValid()){
            $user = $this->getUser();
            $comentario->setPosts($posts);
            $comentario->setUser($user);
            $em->persist($comentario);
            $em->flush();
            $this->addFlash('Exito', Comentarios::REGISTRO_EXITOSO);
            return $this->redirectToRoute('VerPosts',['id'=>$posts->getId()]);
        }
        $pagination = $paginator->paginate(
            $queryComentarios, /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            20 /*limit per page*/
        );

return $this->render('posts/verPosts.html.twig',['posts'=>$posts, 'form'=>$form->createView(), 'comentarios'=>$pagination]);

     }


 
       /**
     * @Route("/mis-posts", name="MisPosts")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
public function MisPosts(PaginatorInterface $paginator,Request $request){
   $user=$this->getUser();

$em= $this->getDoctrine()->getManager();


    $query= $em->getRepository(Posts::class)->findBy(['user'=>$user]);
        $pagination = $paginator->paginate(
            $query, /* query NOT result */
            $request->query->getInt('page', 1),/*page number*/
            3/*limit per page*/
        );
        return $this->render('posts/misPosts.html.twig', [
            'pagination'=>$pagination,
            
        ]);



    
    
}

       /**
     * @Route("/Likes",options={"expose"=true} ,name="Likes")
     * @IsGranted("IS_AUTHENTICATED_FULLY")
     */
public function likes (Request $request){

    if($request->isXmlHttpRequest()){
      

        $em= $this->getDoctrine()->getManager();
          $user=$this->getUser();
          $id=$request->request->get('id');
          $posts= $em->getRepository(Posts::class)->find($id);
          $likes=$posts->getLikes();
          $likes.=$user->getId().',';
          $posts->setLikes($likes);
          $em->flush();
          return new JsonResponse(['likes'=>$likes]);

    }else{
        throw new \Exception('Alert de seguridad');
    }
}


      /**
     * @Route("/delete-posts/{id}", name="posts_delete", methods={"POST"})
     */
    public function delete(Request $request, Posts $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$user->getId(), $request->request->get('_token'))) {
            $entityManager->remove($user);
            $entityManager->flush();
        }

        return $this->redirectToRoute('MisPosts', [], Response::HTTP_SEE_OTHER);
    }


}
