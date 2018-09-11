<?php 
namespace App\Utils\Security;

use Symfony\Component\Security\Http\Authentication\SimplePreAuthenticatorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authentication\Token\PreAuthenticatedToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use App\Utils\Security\ApiUserProvider;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Doctrine\ORM\EntityManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\HttpException;

class ApiAuthenticator implements SimplePreAuthenticatorInterface
{
	protected $userProvider;
	protected $em;
	protected $container;

	public function __construct(ApiUserProvider $userProvider, EntityManager $em, ContainerInterface $container)
	{
		$this->userProvider = $userProvider;
		$this->em = $em;
		$this->container = $container;
	}

	public function createToken(Request $request, $providerKey)
	{
		$route = $request->get("_route");
		if((strpos($route, 'nelmio_api_doc_index') !== false) || (strpos($route, 'admin_') !== false) || $route == "customer_api_citruspay" || $route == "customer_api_payment_response_process" 
				|| $route == "customer_api_checksumgeneration_paytm" || $route == "customer_api_verify_checksum" || $route == "merchant_api_citruspay" || $route == "merchant_api_payment_response_process" || (strpos($route, 'merchant_web') !== false)) {
			$token = "anonymous";
		}else{
			$signature = $request->headers->get('Signature');
			$timeStamp = $request->headers->get('Timestamp');
			$token = $request->headers->get('Token');
			/* var_dump($signature);
			var_dump($timeStamp);
			var_dump($token);die(); */
			
			if (!isset($token)) {
				$token = "anonymous";
			}
			
			if (!$signature || !$timeStamp) {
				throw new HttpException(Response::HTTP_BAD_REQUEST, "Missing Mandatory  Parameters in header");
			}
			
			$privateKey = $this->container->getParameter('app_private_key');//$this->em->getRepository("AppBundle:ApiKey")->getPrivateKey($publicKey);
			if(!$privateKey) {
				throw new HttpException(Response::HTTP_BAD_REQUEST, "Missing PrivateKey Parameters");
			}
			
			$generatedSignature = $this->generateSignature($privateKey, $timeStamp);
			if($signature != $generatedSignature) {
				throw new HttpException(Response::HTTP_BAD_REQUEST, "Invalid Signature");
			}
			
		}
		
		return new PreAuthenticatedToken(
				'anon.',
				$token,
				$providerKey
		);
	}

	public function authenticateToken(TokenInterface $token, UserProviderInterface $userProvider, $providerKey)
	{
		$apiKey = $token->getCredentials();

		$user = $this->userProvider->loadUserByUsername($apiKey);

		return new PreAuthenticatedToken(
				$user,
				$apiKey,
				$providerKey,
				$user->getRoles()
		);
	}

	public function supportsToken(TokenInterface $token, $providerKey)
	{
		return $token instanceof PreAuthenticatedToken && $token->getProviderKey() === $providerKey;
	}
	
	public function generateSignature($privateKey, $timeStamp)
	{
		return urlencode(base64_encode(hash_hmac('sha256', $timeStamp.$privateKey, $privateKey, true)));
	}
}