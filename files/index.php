<?php
// Database connection to fetch doctors
$conn = new mysqli("localhost", "root", "", "hospital_data");
if ($conn->connect_error) {
    // Don't die, just handle gracefully for a public page
    $doctors = [];
} else {
    $result = $conn->query("SELECT Name, Specialization FROM doctors_account ORDER BY Name");
    $doctors = $result ? $result->fetch_all(MYSQLI_ASSOC) : [];
    $conn->close();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hospital Data Management System</title>
    <link rel="stylesheet" href="style.css">
    <style>
                body {
            background-color: #f2f2f2;
        }
        .doctors-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
            gap: 20px;
            padding: 20px 5%;
        }
        .doctor-card {
            background: #fff;
            border: 1px solid #ddd;
            border-radius: 10px;
            padding: 15px;
            text-align: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .doctor-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.1);
        }
        .doctor-card h4 {
            margin: 10px 0 5px;
            color: #205b80;
            font-size: 1.1rem;
        }
        .doctor-card p {
            color: #666;
            font-size: 0.9em;
            margin: 0;
        }
    </style>
</head>

<body>
    <!-- <div class="overlay"></div> -->
    <div class="container">
        <div class="background">
            <img src="/Hospital Data Management System/images/Background.png" alt="Background Image">
        </div>
        <div class="header">
            <h1>CareRecords</h1>
            <ul>
                <li><a href="#" id="home">Home</a></li>
                <li><a href="#" id="doctors">Our Doctors</a></li>
                <li><a href="#" id="about">About Us</a></li>
                <li><a href="#" id="help">Help</a></li>
                <li><a href="#" id="contact">Contact</a></li>
                <li><a href="#" id="login">Sign In</a></li>
            </ul>
            <div class="login">
                <p class="cancle">X</p>
                <h2>Select User Type</h2>
                <button id="patientLogin">Patient Login</button> <br>
                <button id="doctorLogin">Doctor Login</button>
                <button id="adminLogin">Admin Login</button>
            </div>
        </div>
        <div class="content">
            <h2>Welcome to CareRecords</h2>
            <p>Your health data, securely managed.</p>
            <button id="bookAppointment">Book Appointment</button>
        </div>

        <div class="doctors" style="display:none;">
            <h2 style="text-align:center; color: #205b80; margin-top: 4em; margin-bottom: 1em;">Meet Our Specialists</h2>
            <?php if (!empty($doctors)): ?>
                <div class="doctors-grid">
                    <?php foreach ($doctors as $doctor): ?>
                        <div class="doctor-card">
                            <h4><?= htmlspecialchars($doctor['Name']) ?></h4>
                            <p><?= htmlspecialchars($doctor['Specialization']) ?></p>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p style="text-align:center; margin-top: 2em;">Could not load doctor information at this time.</p>
            <?php endif; ?>
        </div>

        <div class="about">
            <div class="patient-section" style="display:none;">
                <h2>Upload Medical Records</h2>
                <form id="patientUploadForm">
                    <input type="file" id="patientFile" name="medicalRecord" required>
                    <button type="submit">Upload</button>
                </form>
            </div>

            <div class="doctor-section" style="display:none;">
                <h2>Patient Medical Record QR Code</h2>
                <div id="qrCode"></div>
                <form id="doctorUploadForm">
                    <input type="file" id="doctorFile" name="doctorFile" required>
                    <button type="submit">Upload</button>
                </form>
            </div>

            <h2>About Us</h2>
            <p class="para1">We are developing a healthcare <br>
                system around you</p>
            <p>We think that everyone should have easy access to excellent <br>
                healthcare. Our aim is to make the procedure as simple as <br>
                possible for our patients and to offer treatment no matter <br>
                where they are — in person or at their convenience.</p>
            <button id="more">Read More</button>
            <img src="/Hospital Data Management System/images/about.png" alt="About Us Image">

        </div>

        <div class="help">
            <h2>Need Help?</h2>
            <p>If you have any questions or need assistance, please reach out to us.</p>
            <button id="contactSupportBtn">Contact Support</button>
        </div>

        <div class="footer">
            <p>&copy; 2025 CareRecords. All rights reserved.</p>
            <div class="social-icons">
                <a href="https://www.facebook.com/"><img src="/Hospital Data Management System/images/facebook.jpg" alt="Facebook"></a>
                <a href="https://x.com/"><img src="/Hospital Data Management System/images/twitter.jpg" alt="Twitter"></a>
                <a href="https://www.instagram.com/"><img src="/Hospital Data Management System/images/insta.jpg" alt="Instagram"></a>
                <a href="https://in.linkedin.com/"><img src="/Hospital Data Management System/images/linkdin.jpg" alt="LinkedIn"></a>
            </div>
        </div>
    </div>

    <div class="readMore" id="aboutus">
        <h2>About Us</h2>
        <p>Our hospital is more than just a healthcare facility; we are a dedicated community of professionals committed
            to your well-being. For over 20 years, we have served this community with unwavering compassion and a focus
            on providing the highest quality medical care.</p>
        <p>Our mission is to combine cutting-edge medical technology with a patient-centered approach. We believe in
            treating not just the illness, but the whole person. Our team of experienced doctors, nurses, and support
            staff works together to create a safe, welcoming, and healing environment for every patient who walks
            through our doors.</p>
        <p>We are proud to offer a wide range of specialized services and are continuously investing in the latest
            advancements to ensure you receive the best possible care. Your health is our priority, and we are honored
            to be a part of your journey to wellness.</p>
    </div>

    <div class="contact">
        <h2>Contact Us</h2>
        <p>If you have any questions or need assistance, please feel free to reach out to us.</p>
        <form action="contact_process.php" method="post">
            <label for="contact_name">Your Name:</label>
            <input type="text" id="name" name="name" required>

            <label for="contact_email">Your Email:</label>
            <input type="email" id="email" name="email" required>

            <label for="contact_message">Message:</label>
            <textarea id="message" name="message" rows="4" required></textarea>

            <button type="submit">Send Message</button>
        </form>
        <p>Alternatively, you can reach us at:</p>
        <p>Email: <a href="mailto:info@myhospital.com">Click Here</a></p>
        <p>Phone: <a href="tel:+91 1234567890">Click Here</a></p>
        <p>Address: 123 Hospital Lane, Dhule, Maharastra, 424002</p>
        <p>We look forward to hearing from you!</p>
    </div>

    <div class="contactSupport">
        <h2>Patient Help Center</h2>
        <div class="faq">
            <h3>Frequently Asked Questions (FAQs)</h3>
            <ul type="disc">
                <li><strong>
                        <p class="que" id="que1">How do I book an appointment? </p>
                    </strong> <br>
                    <p class="ans" id="ans1">You can book an appointment through our website or by
                        calling our helpline.</p>
                </li>
                <li><strong>
                        <p class="que" id="que2">What should I bring to my first appointment? </p>
                    </strong> <br>
                    <p class="ans" id="ans2">Please bring your ID, insurance card,
                        and any relevant medical records.</p>
                </li>
                <li><strong>
                        <p class="que" id="que3"> How can I access my medical records? </p>
                    </strong> <br>
                    <p class="ans" id="ans3">You can access your medical records through
                        our patient portal or by contacting our support team. </p>
                </li>
            </ul>
        </div>
        <div class="helpline">
            <h3>Contact Information</h3>
            <p>For general inquiries, please call us at: <b>+91 1234567890 </b></p>
            <p>For medical emergencies, please dial: <b>+91 9876543210 </b></p>
            <p>You can also reach us via email at: <b>info@myhospital.com</b></p>
        </div>
        <div class="guide">
            <h3>Patient Guide</h3>
            <p>Download our comprehensive Patient Guide which includes details on:</p>
            <ul>
                <li>Patient rights and responsibilities.</li>
                <li>What to expect during your stay.</li>
                <li>Post-discharge care and follow-up.</li>
            </ul>
            <a href="#" class="button">Download Guide (PDF)</a>
        </div>
    </div>
    <script src="script.js"></script>
</body>


</html>
