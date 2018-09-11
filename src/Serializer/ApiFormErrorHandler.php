<?php
namespace App\Serializer;

use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\GenericSerializationVisitor;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\Handler\FormErrorHandler as JMSFormErrorsHandler;
use JMS\Serializer\VisitorInterface;


class ApiFormErrorHandler extends JMSFormErrorsHandler // implements SubscribingHandlerInterface
{
	private $translator;
	
	/* public static function getSubscribingMethods()
	{
		$methods = array();
		foreach (array('xml', 'json', 'yml') as $format) {
			$methods[] = array(
					'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
					'type' => 'Symfony\Component\Form\Form',
					'format' => $format,
			);
			$methods[] = array(
					'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
					'type' => 'Symfony\Component\Form\FormError',
					'format' => $format,
			);
		}
		return $methods;
	} */
	
	public function __construct(TranslatorInterface $translator)
	{
		$this->translator = $translator;
	}
	
	public function serializeFormToJson(JsonSerializationVisitor $visitor, Form $form, array $type)
	{
		return $this->convertFormToArray($visitor, $form);
	}
	
	public function serializeFormErrorToJson(JsonSerializationVisitor $visitor, FormError $formError, array $type)
	{
		return $this->getErrorMessage($formError);
	}
	
	private function getErrorMessage(FormError $error)
	{
		if (null !== $error->getMessagePluralization()) {
			return $this->translator->transChoice($error->getMessageTemplate(), $error->getMessagePluralization(), $error->getMessageParameters());
		}
		return $this->translator->trans($error->getMessageTemplate(), $error->getMessageParameters());
	}
	
	private function convertFormToArray(VisitorInterface $visitor, Form $data)
	{
		$isRoot = null === $visitor->getRoot();
		$form = new \ArrayObject();
		$errors = array();
		foreach ($data->getErrors() as $error) {
			$errors[] = $this->getErrorMessage($error);
		}
		if ($errors) {
			$form['form_errors'] = $errors;
		}
		$children = array();
		foreach ($data->all() as $child) {
			if ($child instanceof Form) {
				$errorList = $this->convertFieldToArray($visitor, $child);
				if($errorList) {
					$children[$child->getName()] = $errorList;
				}
			}
		}
		if ($children) {
			$form['field_errors'] = $children;
		}
		if ($isRoot) {
			$visitor->setRoot($form);
		}
		return $form;
	}
	
	private function convertFieldToArray(VisitorInterface $visitor, Form $data)
	{
		$isRoot = null === $visitor->getRoot();
		$form = new \ArrayObject();
		$errors = array();
		foreach ($data->getErrors() as $error) {
			$errors[] = $this->getErrorMessage($error);
		}
		if ($errors) {
			$form = $errors;
		}
		$children = array();
		foreach ($data->all() as $child) {
			if ($child instanceof Form) {
				$errorList = $this->convertFieldToArray($visitor, $child);
				if(sizeof($errorList) > 0) {
					$children[$child->getName()] = $errorList;
				}
			}
		}
		if ($children) {
			$form = $children;
		} 
		if ($isRoot) {
			$visitor->setRoot($form);
		}
		return $form;
	}
}