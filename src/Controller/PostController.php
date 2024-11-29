<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

use App\Entity\Comment;
use App\Form\CommentType;

use App\Repository\PostRepository;
use App\Entity\Post;
use App\Form\PostType;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\Entity;
use Symfony\Component\HttpFoundation\Request;
use App\Entity\Category;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class PostController extends AbstractController
{
    #[Route('/post', name: 'post_list')]
    public function list(PostRepository $postRepository): Response
    {
        $posts = $postRepository->getAllPost();

        return $this->render('post/index.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/category/{id}/posts', name: 'posts_by_category', requirements: ['id' => '\d+'])]
    public function postsByCategory(int $id, PostRepository $postRepository): Response
    {
        $posts = $postRepository->getPostsByCategory($id);

        return $this->render('post/posts_by_category.html.twig', [
            'posts' => $posts,
        ]);
    }

    #[Route('/post/{id}', name: 'post_show', requirements: ['id' => '\d+'])]
    public function show(
        int $id,
        PostRepository $postRepository,
        Request $request,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer le post avec ses relations (commentaires et utilisateur)
        $post = $postRepository->getPostWithRelations($id);
    
        if (!$post) {
            throw $this->createNotFoundException("Le post avec l'ID $id n'existe pas.");
        }
    
        // Création du formulaire d'ajout de commentaire
        $comment = new Comment();
        $comment->setPost($post);
        $comment->setCreatedAt(new \DateTimeImmutable());
    
        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);
    
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();
    
            $this->addFlash('success', 'Commentaire ajouté avec succès.');
    
            // Redirection pour éviter la soumission multiple
            return $this->redirectToRoute('post_show', ['id' => $id]);
        }
    
        return $this->render('post/show.html.twig', [
            'post' => $post,
            'comments' => $post->getComment(), // Utiliser la méthode existante pour récupérer les commentaires
            'author' => $post->getUser(), // Récupérer l'auteur du post
            'form' => $form->createView(), // Passer le formulaire à la vue
        ]);
    }    

    #[Route('/post/{id}/add-comment', name: 'add_comment', methods: ['POST'])]
    #[IsGranted('ROLE_VISITEUR')]
    public function addComment(int $id, Request $request, PostRepository $postRepository, EntityManagerInterface $entityManager): Response
    {
        $post = $postRepository->find($id);

        if (!$post) {
            throw $this->createNotFoundException("Le post avec l'ID $id n'existe pas.");
        }

        $comment = new Comment();
        $comment->setPost($post);
        $comment->setCreatedAt(new \DateTimeImmutable());

        $form = $this->createForm(CommentType::class, $comment);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($comment);
            $entityManager->flush();

            return $this->redirectToRoute('post_show', ['id' => $id]);
        }

        return $this->render('post/show.html.twig', [
            'post' => $post,
            'form' => $form->createView(),
        ]);
    }

    #[Route('/comment/{id}/delete', name: 'delete_comment', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function deleteComment(int $id, EntityManagerInterface $entityManager): Response
    {
        $comment = $entityManager->getRepository(Comment::class)->find($id);

        if (!$comment) {
            throw $this->createNotFoundException("Le commentaire avec l'ID $id n'existe pas.");
        }

        $entityManager->remove($comment);
        $entityManager->flush();

        $this->addFlash('success', 'Commentaire supprimé avec succès.');

        return $this->redirectToRoute('post_show', ['id' => $comment->getPost()->getId()]);
    }


    #[Route('/post/new', name: 'post_new')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(PostType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $post = new Post();
            $post->setTitle($data['title']);
            $post->setContent($data['content']);
            $post->setPicture($data['picture']);
            $post->setPublishedAt($data['publishedAt']);
            $post->setCategory($data['category']);
            $post->setUser($this->getUser());

            $entityManager->persist($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post ajouté avec succès.');
            
            return $this->redirectToRoute('post_list');
        }

        // Retourne la vue avec le formulaire pour tous les autres cas
        return $this->render('post/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }



    #[Route('/post/{id}/edit', name: 'post_edit')]
    public function edit(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post non trouvé.');
        }

        $form = $this->createForm(PostType::class, [
            'title' => $post->getTitle(),
            'content' => $post->getContent(),
            'picture' => $post->getPicture(),
            'publishedAt' => $post->getPublishedAt(),
            'category' => $post->getCategory(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            $post->setTitle($data['title']);
            $post->setContent($data['content']);
            $post->setPicture($data['picture']);
            $post->setPublishedAt($data['publishedAt']);
            $post->setCategoryId($data['category']);

            $entityManager->flush();

            $this->addFlash('success', 'Post modifié avec succès.');

            return $this->redirectToRoute('post_list');
        }

        return $this->render('post/edit.html.twig', [
            'form' => $form->createView(),
            'post' => $post,
        ]);
    }


    #[Route('/post/{id}/delete', name: 'post_delete', methods: ['POST'])]
    public function delete(int $id, Request $request, EntityManagerInterface $entityManager): Response
    {
        $post = $entityManager->getRepository(Post::class)->find($id);

        if (!$post) {
            throw $this->createNotFoundException('Post non trouvé.');
        }

        if ($this->isCsrfTokenValid('delete' . $post->getId(), $request->request->get('_token'))) {
            $entityManager->remove($post);
            $entityManager->flush();

            $this->addFlash('success', 'Post supprimé avec succès.');
        }

        return $this->redirectToRoute('post_list');
    }

}
