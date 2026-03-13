<?php
// Allow this page to be displayed in an iframe
header('X-Frame-Options: SAMEORIGIN');  // allows iframe embedding
header("Content-Security-Policy: frame-ancestors 'self' http://192.168.11.1"); // optional for extra safety
include __DIR__ . '/config/db.php';
session_start();
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <link rel="icon" href="sdo_logo.png" type="image/png">
  <title>Client Satisfaction Measurement</title>
  <link rel="stylesheet" href="style.css">
  
</head>
<body>
<?php if (isset($_GET['submitted'])): ?>
<script>
    alert("Feedback submitted successfully!");
</script>
<?php endif; ?>

<div class="container">
  <div class="header-bar"></div>
  
  <img src="SDOSPC LOGO.png" style="height: 75px; display: block; margin-left: auto; margin-right: auto;">

  <h1>SDO SAN PEDRO CITY</h1>
  <h2>Client Satisfaction Measurement (CSM)</h2>

  <p><i>The Client Satisfaction (CSM) tracks the customer experience of government offices. 
  Your feedback will help us improve our service. Personal information shared will be kept confidential, and answering this form is voluntary.</i></p>
  <p><strong>ANTI-RED TAPE AUTHORITY PSA Approval No. ARTA-2242-3</strong></p>

  <form method="POST" action="submit_csm.php" id="csmForm">
  
	<!-- Hidden inputs for popup -->
	<input type="hidden" name="full_name" id="hidden_full_name">
	<input type="hidden" name="school_office" id="hidden_school_office">
	
	<label for="email">Email, Full Name, School/Office (optional, to receive a copy of your feedback and Certificate of Appearance):</label>
	<input type="email" id="email" name="email" placeholder="example@email.com" style="width:50%;">
	<input type="full_name" id="full_name" name="full_name" placeholder="Full Name" style="width:50%;">
	<input type="school_office" id="school_office" name="school_office" placeholder="School Name / Office Name" style="width:50%;">

    <label for="age">1. Age</label>
    <input type="number" id="age" name="age" style="width:5%" required>

    <label for="sex">2. Sex:</label>
    <select id="sex" name="sex" style="width:20%" required>
      <option value="">-- Select --</option>
      <option value="Male">Male</option>
      <option value="Female">Female</option>
      <option value="Not Specified">Not Specified</option>
    </select>

    <label for="customer_type">3. Customer Type</label>
    <select id="customer_type" name="customer_type" required>
      <option value="">-- Select --</option>
      <option value="Business">Business (private school, corporations, etc.)</option>
      <option value="Citizen">Citizen (general public, learners, parents, etc.)</option>
      <option value="Government">Government (DepEd or other agencies)</option>
    </select>

    <label for="office">4. Offices:</label>
    <select id="office" name="office_name" required>
      <option value="">-- Select Office --</option>
    </select>

    <label for="sub_office">5. Sub-offices:</label>
    <select id="sub_office" name="sub_office_name">
      <option value="">-- Select Sub-Office --</option>
    </select>

    <label for="service">6. Services:</label>
    <select id="service" name="service_name" required>
      <option value="">-- Select Service --</option>
    </select>

    <label>7. Are you aware of the Citizen's Charter?</label>
    <input type="radio" name="aware" value="Yes" required> Yes
    <input type="radio" name="aware" value="No" required> No

    <label>8. Did you see the Citizen's Charter?</label>
    <input type="radio" name="seen" value="Yes" required> Yes
    <input type="radio" name="seen" value="No" required> No

    <label>9. Did you use the Citizen's Charter as a guide?</label>
    <input type="radio" name="used" value="Yes" required> Yes
    <input type="radio" name="used" value="No" required> No

    <h3 style="margin-top:30px; color:#004aad;">Service Quality Dimension (SQD)</h3>

    <table>
      <tr>
        <th>Question</th>
        <th>Strongly Agree</th>
        <th>Agree</th>
        <th>Neutral</th>
        <th>Disagree</th>
        <th>Strongly Disagree</th>
        <th>N/A</th>
      </tr>
      <tr>
        <td>SQD1 - Acceptable transaction time (Responsiveness)</td>
        <td><input type="radio" name="SQD1" value="5" required></td>
        <td><input type="radio" name="SQD1" value="4" required></td>
        <td><input type="radio" name="SQD1" value="3" required></td>
        <td><input type="radio" name="SQD1" value="2" required></td>
        <td><input type="radio" name="SQD1" value="1" required></td>
        <td><input type="radio" name="SQD1" value="0" required></td>
      </tr>
	  <tr>
	    <td>
		    <p>SQD2 - The office accurately informed and followed the transaction's requirements and steps (Reliability)</p>
        </td>
        <td><input type="radio" name="SQD2" value="5" required></td>
        <td><input type="radio" name="SQD2" value="4" required></td>
        <td><input type="radio" name="SQD2" value="3" required></td>
        <td><input type="radio" name="SQD2" value="2" required></td>
        <td><input type="radio" name="SQD2" value="1" required></td>
        <td><input type="radio" name="SQD2" value="0" required></td>
    </tr>
	<tr>
	    <td>
		    <p>SQD3 - My transaction (including steps and payment) was simple and convenient (Access and Facilities)</p>
        </td>
        <td><input type="radio" name="SQD3" value="5" required></td>
        <td><input type="radio" name="SQD3" value="4" required></td>
        <td><input type="radio" name="SQD3" value="3" required></td>
        <td><input type="radio" name="SQD3" value="2" required></td>
        <td><input type="radio" name="SQD3" value="1" required></td>
        <td><input type="radio" name="SQD3" value="0" required></td>
    </tr>
    <tr>
	    <td>
		    <p>SDQ4 - I easily found information about my transaction from the office or its website (Communication)</p>
        </td>
        <td><input type="radio" name="SQD4" value="5" required></td>
        <td><input type="radio" name="SQD4" value="4" required></td>
        <td><input type="radio" name="SQD4" value="3" required></td>
        <td><input type="radio" name="SQD4" value="2" required></td>
        <td><input type="radio" name="SQD4" value="1" required></td>
        <td><input type="radio" name="SQD4" value="0" required></td>
    </tr>
    <tr>
	    <td>
		    <p>SQD5 - I paid an acceptable amount of fees for my transaction (Costs)</p>
        </td>
        <td><input type="radio" name="SQD5" value="0" required></td>
        <td><input type="radio" name="SQD5" value="0" required></td>
        <td><input type="radio" name="SQD5" value="0" required></td>
        <td><input type="radio" name="SQD5" value="0" required></td>
        <td><input type="radio" name="SQD5" value="0" required></td>
        <td><input type="radio" name="SQD5" value="0" required></td>
    </tr>
	<tr>
	    <td>
		    <p>SQD6 - I am confident my transaction was secure (Integrity)</p>
        </td>
        <td><input type="radio" name="SQD6" value="5" required></td>
        <td><input type="radio" name="SQD6" value="4" required></td>
        <td><input type="radio" name="SQD6" value="3" required></td>
        <td><input type="radio" name="SQD6" value="2" required></td>
        <td><input type="radio" name="SQD6" value="1" required></td>
        <td><input type="radio" name="SQD6" value="0" required></td>
    </tr>
	<tr>
	    <td>
		    <p>SQD7 - The office's support was quick to respond (Assurance)</p>
        </td>
        <td><input type="radio" name="SQD7" value="5" required></td>
        <td><input type="radio" name="SQD7" value="4" required></td>
        <td><input type="radio" name="SQD7" value="3" required></td>
        <td><input type="radio" name="SQD7" value="2" required></td>
        <td><input type="radio" name="SQD7" value="1" required></td>
        <td><input type="radio" name="SQD7" value="0" required></td>
    </tr>
	<tr>
	    <td>
		    <p>SQD8 - I got what I needed from the government office (Outcome)</p>
        </td>
        <td><input type="radio" name="SQD8" value="5" required></td>
        <td><input type="radio" name="SQD8" value="4" required></td>
        <td><input type="radio" name="SQD8" value="3" required></td>
        <td><input type="radio" name="SQD8" value="2" required></td>
        <td><input type="radio" name="SQD8" value="1" required></td>
        <td><input type="radio" name="SQD8" value="0" required></td>
    </tr>

      <!-- You can keep adding your SQD2–SQD8 rows here -->
    </table>

    <label for="suggestion">Remarks / Suggestions:</label>
    <textarea name="suggestion" id="suggestion" rows="4"></textarea> 

	<button type="submit">SUBMIT FEEDBACK</button>
  </form>
</div>

<!-- JS Same as original -->
<script>
  window.onload = function() {
    fetch('get_offices.php')
      .then(response => response.json())
      .then(data => {
        const officeSelect = document.getElementById("office");
        data.forEach(office => {
          let option = document.createElement("option");
          option.value = office.name;
          option.textContent = office.name;
          officeSelect.appendChild(option);
        });
      });
  };

  document.getElementById("office").addEventListener("change", function () {
    const officeId = this.value;
    const subOfficeSelect = document.getElementById("sub_office");
    subOfficeSelect.innerHTML = '<option value="">-- Select Sub-Office --</option>';
    const serviceSelect = document.getElementById("service");
    serviceSelect.innerHTML = '<option value="">-- Select Service --</option>';

    if (officeId) {
      fetch("get_sub_offices.php?office_name=" + encodeURIComponent(officeId))
        .then((res) => res.json())
        .then((data) => {
          if (data.length > 0) {
            data.forEach((sub) => {
              let option = document.createElement("option");
              option.value = sub.name;
              option.textContent = sub.name;
              subOfficeSelect.appendChild(option);
            });
          } else {
            fetch("unit_services.php?office_name=" + encodeURIComponent(officeId))
              .then((res) => res.json())
              .then((services) => {
                services.forEach((srv) => {
                  let option = document.createElement("option");
                  option.value = srv.name;
                  option.textContent = srv.name;
                  serviceSelect.appendChild(option);
                });
              });
          }
        });
    }
  });

  document.getElementById("sub_office").addEventListener("change", function() {
    const subOfficeId = this.value;
    const serviceSelect = document.getElementById("service");
    serviceSelect.innerHTML = '<option value="">-- Select Service --</option>';
    if (subOfficeId) {
      fetch('get_services.php?sub_office_name=' + encodeURIComponent(subOfficeId))
        .then(res => res.json())
        .then(data => {
          data.forEach(svc => {
            let option = document.createElement("option");
            option.value = svc.name;
            option.textContent = svc.name;
            serviceSelect.appendChild(option);
          });
        });
    }
  });
</script>

</body>
</html>
