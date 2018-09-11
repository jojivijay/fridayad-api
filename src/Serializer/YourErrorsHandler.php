<?php 
namespace App\Serializer;

use JMS\Serializer\GenericSerializationVisitor;
use JMS\Serializer\Handler\FormErrorHandler as JMSFormErrorsHandler;
use JMS\Serializer\JsonSerializationVisitor;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormError;
use Symfony\Component\Translation\TranslatorInterface;

class YourErrorsHandler extends JMSFormErrorsHandler
{
    /**
     * @var TranslatorInterface
     */
    private $translation;

    /**
     * @param TranslatorInterface $translator
     */
    public function __construct(TranslatorInterface $translator)
    {
        $this->translation = $translator;
        parent::__construct($translator);
    }

    /**
     * @param JsonSerializationVisitor $visitor
     * @param Form $form
     * @param array $type
     * @return \ArrayObject
     */
    public function serializeFormToJson(JsonSerializationVisitor $visitor, Form $form, array $type)
    {
        return $this->convertToArray($visitor, $form);
    }

    /**
     * @param GenericSerializationVisitor $visitor
     * @param Form $data
     * @return \ArrayObject
     */
    private function convertToArray(GenericSerializationVisitor $visitor, Form $data)
    {
        $isRoot = null === $visitor->getRoot();

        $form = new \ArrayObject();
        $errors = array();
        foreach ($data->getErrors() as $error) {
            $errors[] = $this->getMessageError($error); 
        }

        if ($errors) {
            $form[] = $errors;    // remove key errors
        }

        $children = array();

        foreach ($data->all() as $child) {
            if ($child instanceof Form) {
                $children[$child->getName()] = $this->convertToArray($visitor, $child);
            }
        }

        if ($children) {
            $form = $children; // remove key children
        }

        if ($isRoot) {
            $visitor->setRoot($form);
        }

        return $form;
    }


    /**
     * @param FormError $error
     * @return string
     */
    private function getMessageError(FormError $error)
    {
        if (null !== $error->getMessagePluralization()) {
            return $this->translation->transChoice($error->getMessageTemplate(), $error->getMessagePluralization(), $error->getMessageParameters(), 'validators');
        }

        return $this->translation->trans($error->getMessageTemplate(), $error->getMessageParameters(), 'validators');
    }
}
