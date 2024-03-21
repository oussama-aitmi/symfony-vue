<?php

namespace App\Controller;

use App\Entity\Product;
use App\Form\Type\EditProductFormType;
use App\Form\Type\ProductFormType;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use FOS\RestBundle\Controller\Annotations as Rest;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment;
use Symfony\Component\String\Slugger\SluggerInterface;

/**
 * Controller for product related actions.
 *
 * Class ProductController
 * @package App\Controller
 */

class ProductController extends AbstractController
{
    public function __construct(
        public ProductRepository $productRepository,
        public EntityManagerInterface $entityManager,
        public RequestStack $requestStack,
        public Environment $twig)
    {
    }

    /**
     *
     * @Rest\Get("/api/products/find/all", name="find_all_products")
     */
    public function findAllProducts() : JsonResponse {
        $products = $this->productRepository
            ->findAll();
        return new JsonResponse($products);
    }

    /**
     * @Route("/admin/product/add", name="add_product")
     *
     * @return Response
     */
    public function createProduct(SluggerInterface $slugger) : Response {
        $product = new Product();

        $form = $this->createForm(ProductFormType::class, $product);

        $form->handleRequest($this->requestStack->getCurrentRequest());

        if (!$form->isSubmitted() || !$form->isValid()) {
            $form = $form->createView();
            return new Response($this->twig->render('admin/add_product.html.twig', ['form' => $form]));
        }

        $imageFile = $form->get('image')->getData();

        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                echo $e->getMessage();
            }

            $product->setImageFilename($newFilename);
        }
        $product = $form->getData();

        $this->productRepository->save($product);

        $message = 'Nouveau produit enregistré nommé: ' . $product->getName();
        $product = new Product();
        $form = $this->createForm(ProductFormType::class, $product);
        $form = $form->createView();

        return new Response($this->twig->render('admin/add_product.html.twig', ['form' => $form, 'message' => $message]));

    }

    /**
     * @Route("/api/admin/products/edit", name="edit_product")
     *
     * @param SluggerInterface $slugger
     * @return Response
     */
    public function editProduct(SluggerInterface $slugger) : Response
    {
        $product_id = $this->requestStack->getCurrentRequest()->query->get("id");
        $product = $this->productRepository->find($product_id);
        $form = $this->createForm(EditProductFormType::class, $product);

        $form->handleRequest($this->requestStack->getCurrentRequest());

        if (!$form->isSubmitted() || !$form->isValid()) {
            $form = $form->createView();
            $action = $this->generateUrl('edit_product', ['id' => $product_id]);
            return new Response($this->twig->render('admin/edit_product_form.html.twig', ['form' => $form, 'action' => $action]));

        }

        $imageFile = $form->get('image')->getData();
        if ($imageFile) {
            $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
            $safeFilename = $slugger->slug($originalFilename);
            $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();

            try {
                $imageFile->move(
                    $this->getParameter('images_directory'),
                    $newFilename
                );
            } catch (FileException $e) {
                echo $e->getMessage();
            }
            $product->setImageFilename($newFilename);
        }

        $product = $form->getData();
        $this->productRepository->save($product);

        return $this->redirectToRoute('edit_product_view', ['message' => 'Mise à jour réussie d un produit.']);
    }

    /**
     *
     * @Route("/products/list", name="list_products")
     *
     * @return Response
     */
    public function listAllProducts() : Response {
        $products = $this->productRepository->findAll();

        return new Response($this->twig->render("products/list_products.html.twig", ['products' => $products]));
    }

    /**
     *
     * @Route("/admin/products/edit/view", name="edit_product_view")
     *
     * @return Response
     */
    public function editProductView() : Response {
        $products = $this->productRepository->findAll();
        return new Response($this->twig->render("admin/edit_product.html.twig", ['products' => $products]));
    }

    /**
     *
     * @Rest\Delete("api/admin/products/remove", name="remove_product")
     *
     * @param Request $request
     * @return Response
     */
    public function deleteProduct() : Response {
        $id = $this->requestStack->getCurrentRequest()->query->get('id');
        $product = $this->productRepository->find($id);
        $product_name = $product->getName();
        $this->productRepository->remove($product);
        return new Response('Produit supprimé avec nom: ' . $product_name);
    }

}
