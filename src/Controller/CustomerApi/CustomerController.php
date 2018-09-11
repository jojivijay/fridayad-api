<?php
namespace App\Controller\CustomerApi;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Swagger\Annotations as SWG;
use Nelmio\ApiDocBundle\Annotation\Model;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Encoder\UserPasswordEncoderInterface;
use App\Entity\Customer;
use App\Form\CustomerType;
use FOS\RestBundle\View\View;

class CustomerController extends AbstractController
{
    /**
     * sign up Api
     * @SWG\Response(
     *     response=200,
     *     description="Returns the rewards of an user",
     * )
     * @SWG\Parameter(
     *     name="firstName",
     *     in="query",
     *     type="string",
     *     description="First Name"
     * ),
     * @SWG\Parameter(
     *     name="lastName",
     *     in="query",
     *     type="string",
     *     description="Last Name"
     * ),
     * @SWG\Parameter(
     *     name="email",
     *     in="query",
     *     type="string",
     *     description="email"
     * ),
     * @SWG\Parameter(
     *     name="phoneNo",
     *     in="query",
     *     type="string",
     *     description="Phone Number"
     * ),
     * @SWG\Parameter(
     *     name="password",
     *     in="query",
     *     type="string",
     *     description="Password"
     * )
     * @SWG\Tag(name="User")
     * 
     * 
     * @Route("/signup", name="user_api_signup", methods="POST")
     */
    public function signupAction(Request $request, UserPasswordEncoderInterface $passwordEncoder)
    {
        throw new HttpException(Response::HTTP_BAD_REQUEST, "Please do some thing...");
        /* $customerArray['phone'] = '8147088441';
        $customerArray['Email'] = 'vijay';
        
        return $customerArray;
        var_dump('joji');die('joji'); */
        $requestContent = $request->getContent();
        $requestData = json_decode($requestContent,true);
        if (empty($requestData)) {
            $requestData = $request->request->all();
        }
        $user = new Customer();
        $form = $this->createForm(CustomerType::class, $user);
        $form->submit($requestData);
        //         $form->handleRequest($request); //$form->isSubmitted() && 
        if ($form->isValid()) {
            
            $password = $passwordEncoder->encodePassword($user, $user->getPassword());
            $user->setPassword($password);
            
            $em = $this->getDoctrine()->getManager();
            $em->persist($user);
            $em->flush();
            
            $customerArray['phone'] = $user->getPhoneNo();
            $customerArray['Email'] = $user->getEmail();
            
            return $customerArray;
            
//             return $this->redirectToRoute('replace_with_some_route');
        }
        return $form;
//         return new View($form, Response::HTTP_BAD_REQUEST);
        
        /* return $this->render(
            'registration/register.html.twig',
            array('form' => $form->createView())
            ); */
    }
}