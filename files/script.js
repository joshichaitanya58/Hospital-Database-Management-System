let home = document.getElementById("home");
let about = document.getElementById("about");
let doctors = document.getElementById("doctors");
let help = document.getElementById("help");
let contact = document.getElementById("contact");
let aboutDiv = document.querySelector(".about");
let doctorsDiv = document.querySelector(".doctors");
let helpDiv = document.querySelector(".help");
let backgroundDiv = document.querySelector(".background");
let contentDiv = document.querySelector(".content");
let footerDiv = document.querySelector(".footer");
let moreButton = document.getElementById("more");
let readMoreDiv = document.querySelector(".readMore");
let contactDiv = document.querySelector(".contact");
let contactSupportDiv = document.querySelector(".contactSupport");
let contactSupportBtn = document.getElementById("contactSupportBtn");
let login = document.getElementById("login");
// let overlay = document.querySelector(".overlay");
let que1 = document.getElementById("que1");
let que2 = document.getElementById("que2");
let que3 = document.getElementById("que3");
let ans1 = document.getElementById("ans1");
let ans2 = document.getElementById("ans2");
let ans3 = document.getElementById("ans3");




home.addEventListener("click", function () {
    window.location.href = "index.php";
})

function hideAllSections() {
     aboutDiv.style.display = "none";
    helpDiv.style.display = "none";
    doctorsDiv.style.display = "none";
    backgroundDiv.style.display = "none";
    contentDiv.style.display = "none";
    footerDiv.style.display = "none";
    readMoreDiv.style.display = "none";
    contactDiv.style.display = "none";
    contactSupportDiv.style.display = "none";
}


about.addEventListener("click", function () {
    hideAllSections();
    aboutDiv.style.display = "block";
    aboutDiv.style.marginTop = "4em";
})

doctors.addEventListener("click", function () {
    hideAllSections();
    doctorsDiv.style.display = "block";
    doctorsDiv.style.marginTop = "4em";
})

help.addEventListener("click", function () {
    hideAllSections();
    helpDiv.style.display = "block";
    helpDiv.style.marginTop = "4em";
})

moreButton.addEventListener("click", function () {
    hideAllSections();
    readMoreDiv.style.display = "block";
    readMoreDiv.style.marginTop = "5em";
})

contact.addEventListener("click", function () {
    hideAllSections();
    contactDiv.style.display = "block";
    contactDiv.style.marginTop = "4em";

})

contactSupportBtn.addEventListener("click", function () {
    hideAllSections();
    contactSupportDiv.style.display = "block";
    contactSupportDiv.style.marginTop = "5em";
})

que1.addEventListener("click", function () {
    if (ans1.style.display === "none" || ans1.style.display === "") {
        ans1.style.display = "block";
    } else {
        ans1.style.display = "none";
    }
})

que2.addEventListener("click", function () {
    if (ans2.style.display === "none" || ans2.style.display === "") {
        ans2.style.display = "block";
    } else {
        ans2.style.display = "none";
    }
})

que3.addEventListener("click", function () {
    if (ans3.style.display === "none" || ans3.style.display === "") {
        ans3.style.display = "block";
    } else {
        ans3.style.display = "none";
    }
})

// signin button 
let loginDiv = document.querySelector(".login");
let patientLogin = document.getElementById("patientLogin");
let doctorLogin = document.getElementById("doctorLogin");
let adminLogin = document.getElementById("adminLogin");
let cancle = document.querySelector(".cancle");
let container = document.querySelector(".container");

cancle.addEventListener("click", function () {
    loginDiv.style.display = "none";
    overlay.style.display = "none";
})

login.addEventListener("click", function () {
    loginDiv.style.display = "block";
    // overlay.style.display = "block";
})

patientLogin.addEventListener("click", function () {
    window.location.href = "patientsLogin.php";
})

doctorLogin.addEventListener("click", function () {
    window.location.href = "doctorsLogin.php";
})

adminLogin.addEventListener("click", function () {
    window.location.href = "admin.php";
})

// appoinment form 
let appointmentButton = document.getElementById("bookAppointment");
appointmentButton.addEventListener("click", function () {
    window.location.href = "appoinmentForm.php";
});