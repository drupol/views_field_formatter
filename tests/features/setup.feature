@api @javascript
Feature: Setup
  A user needs to be able to configure the VFF properly.

  Scenario:
    Given I am logged in as a user with the "administrator" role
    And I am on "/admin/structure/types/manage/page/display"
    Then I should see "Body"
    And I select "View" from "Plugin for Body"
    And I wait for AJAX to finish
    Then I should see the button "edit-fields-body-settings-edit"
    Then I press "edit-fields-body-settings-edit"
    And I select "vff_single_test_view::embed_1" from "View"
    Then I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    Then I am on "/node/1"
    And I should see the text "**Node 1 title**"
    And I should see the text "**Node 2 title**"
    And I should see the text "**Node 3 title**"
    Then I am on "/node/2"
    And I should see the text "**Node 1 title**"
    And I should see the text "**Node 2 title**"
    And I should see the text "**Node 3 title**"
    Then I am on "/node/3"
    And I should see the text "**Node 1 title**"
    And I should see the text "**Node 2 title**"
    And I should see the text "**Node 3 title**"

  Scenario:
    Given I am logged in as a user with the "administrator" role
    And I am on "/admin/structure/types/manage/page/display"
    Then I should see "Body"
    And I select "View" from "Plugin for Body"
    And I wait for AJAX to finish
    Then I should see the button "edit-fields-body-settings-edit"
    Then I press "edit-fields-body-settings-edit"
    And I select "vff_single_test_view::embed_1" from "View"
    Then I press "Add a new argument"
    And I wait for AJAX to finish
    Then I should see "Use a static string or a Drupal token."
    And I fill in "Argument" with "[node:nid]"
    Then I check the box "fields[body][settings_edit_form][settings][arguments][0][checked]"
    Then I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    Then I am on "/node/1"
    And I should see the text "**Node 1 title**"
    And I should see the text "**Node 2 title**"
    And I should see the text "**Node 3 title**"
    Then I am on "/node/2"
    And I should see the text "**Node 1 title**"
    And I should see the text "**Node 2 title**"
    And I should see the text "**Node 3 title**"
    Then I am on "/node/3"
    And I should see the text "**Node 1 title**"
    And I should see the text "**Node 2 title**"
    And I should see the text "**Node 3 title**"

  Scenario:
    Given I am logged in as a user with the "administrator" role
    And I am on "/admin/structure/types/manage/page/display"
    Then I should see "Body"
    And I select "View" from "Plugin for Body"
    And I wait for AJAX to finish
    Then I should see the button "edit-fields-body-settings-edit"
    Then I press "edit-fields-body-settings-edit"
    And I select "vff_single_test_view::embed_2" from "View"
    Then I press "Add a new argument"
    And I wait for AJAX to finish
    Then I should see "Use a static string or a Drupal token."
    And I fill in "Argument" with "[node:nid]"
    Then I check the box "fields[body][settings_edit_form][settings][arguments][0][checked]"
    Then I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    Then I am on "/node/1"
    And I should see the text "**Node 1 title**"
    And I should not see the text "**Node 2 title**"
    And I should not see the text "**Node 3 title**"
    Then I am on "/node/2"
    And I should not see the text "**Node 1 title**"
    And I should see the text "**Node 2 title**"
    And I should not see the text "**Node 3 title**"
    Then I am on "/node/3"
    And I should not see the text "**Node 1 title**"
    And I should not see the text "**Node 2 title**"
    And I should see the text "**Node 3 title**"


