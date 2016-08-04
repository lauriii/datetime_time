<?php

/**
 * @file
 * Contains \Drupal\datetime_time\Tests\DateTimeTimeTest.
 *
 * @see \Drupal\datetime\Tests\DateTimeFieldTest
 */

namespace Drupal\datetime_time\Tests;

use Drupal\Component\Render\FormattableMarkup;
use Drupal\Component\Utility\Unicode;
use Drupal\simpletest\WebTestBase;

/**
 * Tests Datetime Time widget functionality.
 *
 * @group datetime_time
 */
class DateTimeTimeTest extends WebTestBase {

  /**
   * Modules to enable.
   *
   * @var array
   */
  public static $modules = ['node', 'entity_test', 'datetime', 'datetime_time', 'field_ui'];

  /**
   * The default display settings to use for the formatters.
   */
  protected $defaultSettings;

  /**
   * An array of display options to pass to entity_get_display()
   *
   * @var array
   */
  protected $displayOptions;

  /**
   * A field storage to use in this test class.
   *
   * @var \Drupal\field\Entity\FieldStorageConfig
   */
  protected $fieldStorage;

  /**
   * The field used in this test class.
   *
   * @var \Drupal\field\Entity\FieldConfig
   */
  protected $field;

  /**
   * {@inheritdoc}
   */
  protected function setUp() {
    parent::setUp();

    $web_user = $this->drupalCreateUser([
      'access content',
      'view test entity',
      'administer entity_test content',
      'administer entity_test form display',
      'administer content types',
      'administer node fields',
    ]);
    $this->drupalLogin($web_user);

    // Create a field with settings to validate.
    $field_name = Unicode::strtolower($this->randomMachineName());
    $this->fieldStorage = entity_create('field_storage_config', [
      'field_name' => $field_name,
      'entity_type' => 'entity_test',
      'type' => 'datetime',
      'settings' => ['datetime_type' => 'datetime'],
    ]);
    $this->fieldStorage->save();
    $this->field = entity_create('field_config', [
      'field_storage' => $this->fieldStorage,
      'bundle' => 'entity_test',
      'required' => TRUE,
    ]);
    $this->field->save();

    entity_get_form_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'default')
      ->setComponent($field_name, [
        'type' => 'datetime_time',
      ])
      ->save();

    $this->defaultSettings = [
      'timezone_override' => '',
    ];

    $this->displayOptions = [
      'type' => 'datetime_default',
      'label' => 'hidden',
      'settings' => ['format_type' => 'medium'] + $this->defaultSettings,
    ];
    entity_get_display($this->field->getTargetEntityTypeId(), $this->field->getTargetBundle(), 'full')
      ->setComponent($field_name, $this->displayOptions)
      ->save();
  }

  /**
   * Tests Time Widget functionality.
   */
  function testDateTimeTimeWidget() {
    $field_name = $this->fieldStorage->getName();

    // Display creation form.
    $this->drupalGet('entity_test/add');

    $this->assertFieldByName("{$field_name}[0][value][time]", '', 'Time element found.');
    $this->assertNoFieldByName("{$field_name}[0][value][date]", '', 'Date element not found.');

    // Submit a valid time and ensure it is accepted.
    $date_value = array('time' => '14:15');
    $edit = array();
    foreach ($date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, 'Save');
    preg_match('|entity_test/manage/(\d+)|', $this->url, $match);
    $id = $match[1];
    $this->assertText(new FormattableMarkup('entity_test @id has been created.', ['@id' => $id]));

    // Submit a invalid time and ensure it is rejected.
    $date_value = ['time' => '123456'];
    $edit = [];
    foreach ($date_value as $part => $value) {
      $edit["{$field_name}[0][value][$part]"] = $value;
    }

    $this->drupalPostForm(NULL, $edit, 'Save');
    $this->assertText('date is invalid.');
  }
}
