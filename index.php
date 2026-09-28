
<!DOCTYPE html>
<html>
<head>

    <title>WorkPoint</title>

    <style>

        /* Basic page style */
        body {
            margin: 0;
            font-family: Arial, sans-serif;
        }

 
        /* Main section */
        main {
            padding: 30px;
        }

        .hero {
            text-align: center;
            padding: 30px;
            background-image: url('/uploads/office1.jpeg');
        }

        .hero h1 {
            font-size: 36px;
        }

        .hero p {
            font-size: 18px;
        }

        .button {
            display: inline-block;
            padding: 10px 20px;
            background-color: black;
            color: white;
            text-decoration: none;
            margin-top: 10px;
        }


        /* Main tables */
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
            margin-bottom: 40px;
        }

        td {
            padding: 20px;
            border: 1px solid #ddd;
            vertical-align: top;
        }

        th {
            padding: 15px;
            border: 1px solid #ddd;
        }
    </style>

</head>

<body>


<!-- ================= HEADER ================= -->

<?php
include __DIR__."/includes/header1.php";
?>



<!-- ================= MAIN SECTION ================= -->

<main>


    <!-- Hero Section -->

    <div class="hero">

        <h1>Welcome to SkillSprout</h1>

        <p>
            A simple platform where people can post tasks,
            complete tasks and earn Work Points.
        </p>

        <a href="register.php" class="button">
            Get Started
        </a>

    </div>



    <!-- ================= HOW WORKPOINT WORKS ================= -->

    <h2>How WorkPoint Works</h2>

    <table>

        <tr>

            <td>

                <h3>1. Create a Task</h3>

                <p>
                    Post a task and set the number of
                    Work Points you want to offer.
                </p>

            </td>


            <td>

                <h3>2. Find a Task</h3>

                <p>
                    Browse available tasks and apply
                    for a task that matches your skills.
                </p>

            </td>


            <td>

                <h3>3. Complete the Task</h3>

                <p>
                    Complete the assigned work and
                    submit it to the task creator.
                </p>

            </td>


            <td>

                <h3>4. Earn Work Points</h3>

                <p>
                    After the work is approved,
                    Work Points are added to your wallet.
                </p>

            </td>

        </tr>

    </table>




<!-- ================= POPULAR DOMAINS ================= -->

<h2>Popular Domains</h2>

<table>

    <tr>

        <td width="33%">
            <h3>Web Development</h3>
            <p>Create websites and web applications.</p>
        </td>

        <td width="33%">
            <h3>Graphic Design</h3>
            <p>Design posters, logos and graphics.</p>
        </td>

        <td width="34%">
            <h3>Content Writing</h3>
            <p>Write articles, descriptions and documents.</p>
        </td>

    </tr>

    <tr>

        <td width="33%">
            <h3>Data Entry</h3>
            <p>Enter and organize useful information.</p>
        </td>

        <td width="33%">
            <h3>Programming</h3>
            <p>Work on programming and coding tasks.</p>
        </td>

        <td width="34%">
            <h3>Other Skills</h3>
            <p>Find tasks based on your own skills.</p>
        </td>

    </tr>

</table>



<!-- ================= WHY WORKPOINT ================= -->

<h2>Why Use WorkPoint?</h2>

<table>

    <tr>

        <td width="33%" class = "translates">
            <h3>Learn</h3>
            <p>
                Improve your skills by working on
                different types of tasks.
            </p>
        </td>

        <td width="33%">
            <h3>Earn Work Points</h3>
            <p>
                Complete tasks and receive Work Points
                after your work is approved.
            </p>
        </td>

        <td width="34%">
            <h3>Use Your Skills</h3>
            <p>
                Find tasks related to your skills
                and interests.
            </p>
        </td>

    </tr>

    <tr>

        <td width="33%">
            <h3>Build Experience</h3>
            <p>
                Gain practical experience by completing
                different tasks.
            </p>
        </td>

        <td width="33%">
            <h3>Help Others</h3>
            <p>
                Use your knowledge to help other
                WorkPoint users.
            </p>
        </td>

        <td width="34%">
            <h3>Simple Platform</h3>
            <p>
                Easily find tasks, apply for work
                and manage your Work Points.
            </p>
        </td>

    </tr>

</table>




    <!-- ================= FINAL CTA ================= -->

    <table>

        <tr>

            <td style="text-align: center;">

                <h2>Ready to Get Started?</h2>

                <p>
                    Create your WorkPoint account and
                    start exploring tasks.
                </p>

                <a href="register.php" class="button">
                    Create Account
                </a>
            </td>
        </tr>
    </table>
</main>



<?php
include __DIR__."/includes/footer1.php";
?>


</body>
</html>

