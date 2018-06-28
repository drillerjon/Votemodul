<?php

namespace Drupal\vote\Form;

use Drupal\Core\Form\FormBase;
use Drupal\Core\DependencyInjection\ContainerInjectionInterface;
use Drupal\Core\Form\FormInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\Messenger\MessengerTrait;
use Drupal\Core\Session\AccountProxyInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\votemodul\voteStorage;
use Symfony\Component\DependencyInjection\ContainerInterface;


/**
 * Implements the build demo form controller.
 *
 * This example uses the Messenger service to demonstrate the order of
 * controller method invocations by the form api.
 *
 * @see \Drupal\Core\Form\FormBase
 * @see \Drupal\Core\Form\ConfigFormBase
 */
class Miau implements FormInterface, ContainerInjectionInterface{

  use StringTranslationTrait;
  use MessengerTrait;

  /**
   * Counter keeping track of the sequence of method invocation.
   *
   * @var int
   */
  protected $currentUser;

  /**
   * {@inheritdoc}
   *
   * We'll use the ContainerInjectionInterface pattern here to inject the
   * current user and also get the string_translation service.
   */
  public static function create(ContainerInterface $container) {
    $form = new static(
      $container->get('current_user')
    );
    // The StringTranslationTrait trait manages the string translation service
    // for us. We can inject the service here.
    $form->setStringTranslation($container->get('string_translation'));
    $form->setMessenger($container->get('messenger'));
    return $form;
  }

  /**
   * Construct the new form object.
   */
  public function __construct(AccountProxyInterface $current_user) {
    $this->currentUser = $current_user;
  }



  /**
   * {@inheritdoc}
   */
  public function buildForm(array $form, FormStateInterface $form_state) {
  $form = [];

     $form['message'] = [
       '#markup' => $this->t('Add an entry to the dbtng_example table.'),
     ];

     $form['vote'] = [
       '#type' => 'fieldset',
       '#title' => $this->t('Vote'),
     ];
     $form['vote']['votes'] = [
             '#type' => 'select',
             '#title' => $this->t('Favorite color'),
             '#options' => [
               'cat' => $this->t('Katze'),
               'dog' => $this->t('Hund'),
               'duck' => $this->t('Ente'),
             ],
             '#empty_option' => $this->t('-select-'),
           ];
     $form['vote']['submit'] = [
       '#type' => 'submit',
       '#value' => $this->t('Vote'),
     ];

     return $form;
   }

  /**
   * {@inheritdoc}
   */
  public function getFormId() {
    return 'form_api_example_build_form';
  }


  /**
   * Implements a form submit handler.
   *
   * The submitForm method is the default method called for any submit elements.
   *
   * @param array $form
   *   The render array of the currently built form.
   * @param \Drupal\Core\Form\FormStateInterface $form_state
   *   Object describing the current state of the form.
   */


  public function validateForm(array &$form, FormStateInterface $form_state) {
    // Verify that the user is logged-in.
    if ($this->currentUser->isAnonymous()) {
      $form_state->setError($form['favorite'], $this->t('You must be logged in to add values to the database.'));
    }
  }


  public function submitForm(array &$form, FormStateInterface $form_state) {
    // Gather the current user so the new record has ownership.
    $account = $this->currentUser;
    // Save the submitted entry.
    $entry = [
      'vote' => $form_state->getValue('votes'),
      'uid' => $account->id(),
    ];
    $return = voteStorage::insert($entry);
    if ($return) {
      $this->messenger()->addMessage($this->t('Created entry @entry', ['@entry' => print_r($entry, TRUE)]));
    }
  }

}
