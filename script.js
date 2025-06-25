// welcome user when page loads
function welcomeUser() {
  // get the username from cookies if it exists
  const username = getCookie("username");

  // if there's a username saved, say welcome back
  if (username) {
    console.log("Welcome back, " + username); 
  }
}

// helper function to get cookie value by name
function getCookie(name) {
  const value = `; ${document.cookie}`;
  const parts = value.split(`; ${name}=`);
  if (parts.length === 2) return parts.pop().split(';').shift();
}

// validate form before submission - this runs when user clicks submit
function validateForm() {
  // get username and password values from the form
  const user = document.forms["auth"]["username"].value;
  const pass = document.forms["auth"]["password"].value;
  
  // check if fields are empty
  if (user === "" || pass === "") {
    alert("All fields must be filled out");
    return false; // stops form from submitting
  }
  
  // check username length
  if (user.length < 3) {
    alert("Username must be at least 3 characters long");
    return false;
  }
  
  // check password length
  if (pass.length < 6) {
    alert("Password must be at least 6 characters long");
    return false;
  }
  
  return true; // allow form to submit
}

// add a nice shadow effect when user scrolls down
window.addEventListener('scroll', function() {
  const header = document.querySelector('header');
  // if user scrolled down more than 50 pixels, make shadow darker
  if (window.scrollY > 50) {
    header.style.boxShadow = '0 4px 15px rgba(0,0,0,0.2)';
  } else {
    // otherwise use lighter shadow
    header.style.boxShadow = '0 2px 10px rgba(0,0,0,0.1)';
  }
});

// add interactive effects when page loads
document.addEventListener('DOMContentLoaded', function() {
  // run the welcome function
  welcomeUser();
  
  // add click effects to buttons - makes them feel more interactive
  const buttons = document.querySelectorAll('.form-button, .cta-button, .dashboard-link');
  buttons.forEach(button => {
    // when user presses down on button, make it slightly smaller
    button.addEventListener('mousedown', function() {
      this.style.transform = 'scale(0.95)';
    });
    
    // when user releases mouse, return to normal size
    button.addEventListener('mouseup', function() {
      this.style.transform = 'scale(1)';
    });
    
    // also return to normal if mouse leaves the button
    button.addEventListener('mouseleave', function() {
      this.style.transform = 'scale(1)';
    });
  });
  
  // add loading effect for form submission
  const forms = document.querySelectorAll('form');
  forms.forEach(form => {
    form.addEventListener('submit', function() {
      const submitBtn = this.querySelector('button[type="submit"]');
      if (submitBtn) {
        // small delay to allow form validation to complete first
        setTimeout(() => {
          submitBtn.innerHTML = 'Processing...';
          submitBtn.disabled = true;
        }, 100);
      }
    });
  });
});

// console message to show script loaded successfully
console.log('🌱 SpareBite loaded successfully!'); 