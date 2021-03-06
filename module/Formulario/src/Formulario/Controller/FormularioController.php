<?php
/**
 * Zend Framework (http://framework.zend.com/)
 *
 * @link      http://github.com/zendframework/ZendSkeletonApplication for the canonical source repository
 * @copyright Copyright (c) 2005-2012 Zend Technologies USA Inc. (http://www.zend.com)
 * @license   http://framework.zend.com/license/new-bsd New BSD License
 */

namespace Formulario\Controller;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;
use Zend\Db\Adapter\Adapter;
use Zend\Validator\File\Size; 

use Formulario\Form\Formularios; /* desde aqui se generan los campos del formulario  */
use Formulario\Modelo\Profile;  /*  Desde aqui se insertan los filtros para validar la informacion enviada en los campos del formulario*/
use Formulario\Modelo\Entity\Usuarios;  /*  Desde aqui se hace todoo lo concerniente con la base de datos */


class FormularioController extends AbstractActionController
{
    public $dbAdapter;
    public function indexAction()
    {
        return new ViewModel();
    }

    /**
     * @return \Zend\Http\Response|ViewModel
     */
    public function registroAction()
    {
        $form=new Formularios();  // Desde aqui se crea la instancia de la clase Formularios
		
		/*INICIA EL LLENADO DE OPCIONES DE LOS SELECT Y CHECKBOX*/
		$form->get("genero")->setValueOptions(array('f'=>'Femenino','m'=>'Masculino'));
        $form->get("lenguaje")->setValueOptions(array('0'=>'Ingles','1'=>'Espanol'));
		$form->get("oculto")->setAttribute("value","87");
        $form->get("preferencias")->setValueOptions(array('m'=>'Musica','d'=>'Deporte','o'=>'Ocio'));
		/*TERMINA EL LLENADO DE OPCIONES DE LOS SELECT Y CHECKBOX*/
        
		$request = $this->getRequest();
		if($request->isPost()){ // Se valida cuando se manda el envio del formualario

            // Usar filtros al campo file para vaidar el tipo de archivo

            $profile = new Profile(); // ESTO VALIDA SOLO NOMBRE Y UPLOAD Y LA CONTRASEÑA
            $form->setInputFilter($profile->getInputFilter());

            $nonFile = $request->getPost()->toArray();
            $File    = $this->params()->fromFiles('fileupload');
            $data = array_merge(
                $nonFile,
                array('fileupload'=> $File['name'])
            );

            //set data post and file ...
            $form->setData($data);

            if ($form->isValid()) {
                $size = new Size(array('min'=>'0.001MB')); //2MB
                $adapter = new \Zend\File\Transfer\Adapter\Http();
                $extensionvalidator = new \Zend\Validator\File\Extension(array('extension'=>array('jpg','png'))); // solo JPG y PNG
                $adapter->setValidators(array($size,$extensionvalidator), $File['name']);
                if (!$adapter->isValid()){
                    $dataError = $adapter->getMessages();
                    $error = array();
                    foreach($dataError as $key=>$row)
                    {
                        $error[] = $row;
                    }
                    $form->setMessages(array('fileupload'=>$error ));

                }
                else {
                    $adapter->setDestination(dirname(__DIR__).'/uploads');
                    if ($adapter->receive($File['name'])) {
                        $profile->exchangeArray($form->getData());
                        echo 'Profile Name '.$profile->profilename.' upload '.$profile->fileupload;
                        //Poner un alert y redirección

                        /*INICIA AGREGAGADO LGS */
                        $this->dbAdapter=$this->getServiceLocator()->get('Zend\Db\Adapter');
                        $u=new Usuarios($this->dbAdapter); // Instancia de la clase usuarios que es la que trabaja con la tablas usuario de la base de  datos
                        $data = $this->request->getPost(); // recupero los datos que vienen del formulario y lo pongo en $data
                        $u->addUsuario($data, $profile->fileupload); // llamo al metodo que inserta en la base de datos
                        //return $this->redirect()->toUrl($this->getRequest()->getBaseUrl().'/formulario/formulario/registro/1');
                        /*TERMINA AGREGAGADO LGS */
                    }
                }
            }
            /*else{
                echo '<pre>';
                print_r($form->getMessages());
                echo '</pre>';
                exit;

            }*/
            // Hasta aqui voy validando que vengan los datos correctamente



        }

        return array('form' => $form);

    }
   
}
