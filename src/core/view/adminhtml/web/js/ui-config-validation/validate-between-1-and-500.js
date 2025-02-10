define(
  ['jquery'],
  function ($) {
    'use strict';

    return function (target) {
      $.validator.addMethod(
          'validate-between-1-and-500',
          function (value) {
            var number = parseInt(value);
            return !isNaN(number) && number >= 1 && number <= 500;
          },
          $.mage.__('Please enter a value between 1 and 500')
      );
      return target;
    };
  }
);