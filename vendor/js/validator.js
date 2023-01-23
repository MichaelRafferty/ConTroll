// Wait for the DOM to be ready
$(function() {
  // Initialize form validation on the registration form.
  $("#vendor_reg").validate({
    // Specify validation rules
    rules: {
      name: "required",
      email: {
        required: true,
        email: true
      },
      password: {
        required: true,
        minlength: 5
      },
      password2: {
        required: true,
        equalTo: '#pw1'
      },
    },
    // Specify validation error messages
    messages: {
      name: "Please enter the name you wish to appear under",
      password: {
        required: "Please provide a password",
        minlength: "Your password must be at least 5 characters long"
      },
      email: "Please enter a valid email address",
      password2: {
        required: "Please provide a password",
        equalTo: "Please enter the same password as above"
      }
    },
    // Make sure the form is submitted to the destination defined
    // in the "action" attribute of the form when valid
    submitHandler: function(form) {
      form.submit();
    }
  });
  $("#vendor_update").validate({
    rules: {
        website: {
            required: true,
            url: true
        },
        description: "required"
    },
    messages: {
        website: "Please provide a valid URL, make sure you include https://<br/>",
        description: "Please provide a Description"
    },
    // Make sure the form is submitted to the destination defined
    // in the "action" attribute of the form when valid
    submitHandler: function(form) {
      form.submit();
    }
  });
  $("#changepw").validate({
    rules: {
        oldPassword: "required",
        password: {
            required: true
//            notEqual: "#oldPw"
        },
        password2: {
            required: true,
            equalTo: "#pw2"
        }
    },
    messages: {
      oldPassword: "Please enter your current password",
      password: {
        required: "Please provide a password"
//       notEqual: "Your new password can't be the same as your current password"
      },
      password2: {
        required: "Please provide a password",
        equalTo: "Please enter the same password as above"
      }
    },
    // Make sure the form is submitted to the destination defined
    // in the "action" attribute of the form when valid
    submitHandler: function(form) {
      form.submit();
    }
  });
});

