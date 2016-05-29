<?php

namespace Drupal\file_encrypt\Hooks;

use Drupal\Core\Field\FieldTypePluginManagerInterface;
use Drupal\Core\Form\FormStateInterface;
use Drupal\Core\StringTranslation\StringTranslationTrait;
use Drupal\encrypt\EncryptionProfileManagerInterface;
use Drupal\field\FieldStorageConfigInterface;
use Drupal\file\Plugin\Field\FieldType\FileItem;

class FieldStorageConfigEditFormAlter {

  use StringTranslationTrait;

  /** @var \Drupal\Core\Field\FieldTypePluginManagerInterface */
  protected $fieldTypeManager;

  /** @var \Drupal\encrypt\EncryptionProfileManagerInterface */
  protected $encryptionProfileManager;

  /**
   * Creates a new FieldStorageConfigEditFormAlter instance.
   *
   * @param \Drupal\Core\Field\FieldTypePluginManagerInterface $field_type_manager
   *   The field type plugin manager.
   * @param \Drupal\encrypt\EncryptionProfileManagerInterface $encryption_profile_manager
   *   The encryption profile manager.
   */
  public function __construct(FieldTypePluginManagerInterface $field_type_manager, EncryptionProfileManagerInterface $encryption_profile_manager) {
    $this->fieldTypeManager = $field_type_manager;
    $this->encryptionProfileManager = $encryption_profile_manager;
  }

  /**
   * Implements hook_form_field_storage_config_edit_form_alter().
   */
  public function alterForm(array &$form, FormStateInterface $form_state) {
    /** @var \Drupal\field\FieldStorageConfigInterface $field_storage_config */
    $field_storage_config = $form_state->getFormObject()->getEntity();
    $field_type_definition = $this->fieldTypeManager->getDefinition($field_storage_config->getType());
    if (is_subclass_of($field_type_definition['class'], FileItem::class)) {
      $this->doFormAlter($field_storage_config, $form, $form_state);
    }
  }

  /**
   * @param \Drupal\field\FieldStorageConfigInterface $field_storage_config
   * @param array $form
   */
  protected function doFormAlter(FieldStorageConfigInterface $field_storage_config, array &$form) {
    $options = $this->encryptionProfileManager->getEncryptionProfileNamesAsOptions();
    if (empty($options)) {
      $form['settings']['encryption_profile'] = [
        '#markup' => $this->t('No encryption profile found.'),
      ];
    }
    else {
      $form['settings']['encryption_profile'] = [
        '#type' => 'radios',
        '#title' => $this->t('Encryption profile'),
        '#options' => $options,
        '#default_value'=> $field_storage_config->getThirdPartySetting('file_encrypt', 'encryption_profile', NULL),
        '#states' => [
          'visible' => [
            'input[name="settings[uri_scheme]"]' => ['value' => 'encrypt'],
          ],
        ],
      ];
    }

    $form['#entity_builders'][] = static::class . '::buildEntity';
  }

  public static function buildEntity($entity_type_id, FieldStorageConfigInterface $field_storage_config, $form, FormStateInterface $form_state) {
    if ($encryption_profile = $form_state->getValue(['settings', 'encryption_profile'])) {
      $field_storage_config->setThirdPartySetting('file_encrypt', 'encryption_profile', $encryption_profile);
    }
  }

}
