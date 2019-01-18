@api @javascript
Feature: Setup
  A user needs to be able to configure the VFF properly.

  Background:
    Given "page" content:
      | title        | body        |
      | Node 1 title | Node 1 body |
      | Node 2 title | Node 2 body |
      | Node 3 title | Node 3 body |
    And I am logged in as a user with the "administrator" role
    And I set the "views_field_formatter" formatter to the field "body" of the "page" bundle of "node" entity

  Scenario:
    When I am on "/admin/structure/types/manage/page/display"
    Then I should see the text "Not configured yet."

  Scenario:
    When I am on "/admin/structure/types/manage/page/display"
    And I press "body_settings_edit"
    And I select "vff_single_test_view::embed_1" from "View"
    And I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    When I am viewing my "page" with the title "Node 1 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am viewing my "page" with the title "Node 2 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am viewing my "page" with the title "Node 3 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"

  Scenario:
    When I am on "/admin/structure/types/manage/page/display"
    And I press "body_settings_edit"
    And I select "vff_single_test_view::embed_1" from "View"
    And I press "Add a new argument"
    And I wait for AJAX to finish
    Then I should see "Use a static string or a Drupal token."
    And I fill in "Argument" with "[node:nid]"
    And I check the box "fields[body][settings_edit_form][settings][arguments][0][checked]"
    And I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    When I am viewing my "page" with the title "Node 1 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am viewing my "page" with the title "Node 2 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"
    When I am viewing my "page" with the title "Node 3 title"
    Then I should see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"

  Scenario:
    When I am on "/admin/structure/types/manage/page/display"
    And I press "body_settings_edit"
    And I select "vff_single_test_view::embed_2" from "View"
    And I press "Add a new argument"
    And I wait for AJAX to finish
    Then I should see "Use a static string or a Drupal token."
    And I fill in "Argument" with "[node:nid]"
    And I check the box "fields[body][settings_edit_form][settings][arguments][0][checked]"
    And I press "Update"
    And I wait for AJAX to finish
    And I press "Save"
    Then I should see "Your settings have been saved."
    When I am viewing my "page" with the title "Node 1 title"
    Then I should see the text "**Node 1 title**"
    Then I should not see the text "**Node 2 title**"
    Then I should not see the text "**Node 3 title**"
    When I am viewing my "page" with the title "Node 2 title"
    Then I should not see the text "**Node 1 title**"
    Then I should see the text "**Node 2 title**"
    Then I should not see the text "**Node 3 title**"
    When I am viewing my "page" with the title "Node 3 title"
    Then I should not see the text "**Node 1 title**"
    Then I should not see the text "**Node 2 title**"
    Then I should see the text "**Node 3 title**"


