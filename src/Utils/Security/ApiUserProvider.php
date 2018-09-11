<?php
namespace App\Utils\Security;

use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Core\User\User;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

class ApiUserProvider implements UserProviderInterface
{
	private $em;
	
	public function __construct(EntityManager $em)
	{
		$this->em = $em;
	}

	public function loadUserByUsername($username)
	{
		if($username == 'anonymous') {
			return new User(
					$username,
					null,
					array('IS_AUTHENTICATED_ANONYMOUSLY')
			);
		}
		
		$request = Request::createFromGlobals();
		$api = explode("/", $request->getPathInfo());
		if (isset($api) && $api['1'] == "customerapi") {
			return $this->em->getRepository('AppBundle:CustomerReg')->loadUserByAuthToken($username);
		}
		if (isset($api) && ($api['1'] == "merchantapi" || $api['1'] == "kioskapi")) {
			$smbAdmin = ($request->headers->get('Smbadmin') == 1) ? intval($request->headers->get('Smbadmin')) : 0;
			return $this->em->getRepository('AppBundle:Merchant')->loadMerchantByAuthToken($username, $smbAdmin);
		}
		if (isset($api) && $api['1'] == "stylistapi") {
			return $this->em->getRepository('AppBundle:Stylist')->loadStylistByAuthToken($username);
		}
		
	}

	public function refreshUser(UserInterface $user)
	{
		throw new UnsupportedUserException();
	}

	public function supportsClass($class)
	{
		return ('Symfony\Component\Security\Core\User\User' === $class || 'AppBundle\Entity\CustomerReg' === $class); 
	}
}