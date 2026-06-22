<?php
session_start();

if (!isset($_SESSION['user_role']) || !in_array($_SESSION['user_role'], ['Manager','Admin'])) {

    header("Location: login.php");
    exit();
}

$conn = new mysqli("localhost","root","","leave_management");

$message="";


// APPROVE
if(isset($_POST['approve'])){

    $id=$_POST['emp'];

    $stmt=$conn->prepare(
        "
        UPDATE employees
        SET
        password=password_request,
        password_request=NULL,
        password_status='Approved'
        WHERE emp_id=?
        "
    );

    $stmt->bind_param("s",$id);

    if($stmt->execute()){

        $message="<div class='alert success'>✅ Password request approved.</div>";

    }

    $stmt->close();

}


// REJECT
if(isset($_POST['reject'])){

    $id=$_POST['emp'];

    $stmt=$conn->prepare(
        "
        UPDATE employees
        SET
        password_request=NULL,
        password_status='Rejected'
        WHERE emp_id=?
        "
    );

    $stmt->bind_param("s",$id);

    if($stmt->execute()){

        $message="<div class='alert error'>❌ Password request rejected.</div>";

    }

    $stmt->close();

}

?>
<!DOCTYPE html>

<html>
    <head>
        <meta charset="UTF-8">
        <title>DayAway | Password Requests</title>
        <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <link rel="stylesheet" href="leave_management.css">
    </head>

    <body class="manager-layout">
        <aside id="sidebar">
            <div class="brand"><i class="fa-solid fa-layer-group"></i><span>DayAway</span></div>
            <ul class="nav-links">
                <li><a href="index.php"><i class="fa-solid fa-house"></i>Dashboard</a></li>
                <li><a href="requests.php"><i class="fa-solid fa-calendar-check"></i>Leave Requests</a></li>
                <li><a href="employees.php"><i class="fa-solid fa-users"></i>Employees</a></li>
                <li> <a href="analytics.php"><i class="fa-solid fa-chart-pie"></i>Analytics</a></li>
                <li class="active"><a href="password_requests.php"><i class="fa-solid fa-key"></i>Password Requests</a></li>
                <li style="margin-top:auto;"><a href="logout.php" style="color:#ef4444;" onclick="return confirmLogout()"><i class="fa-solid fa-power-off"></i>Logout</a></li>
            </ul>
        </aside>


        <main>
            <header>
                <div>
                <h1>Password Change Requests</h1>
                <p style="color:var(--text-muted);">Manager approval center</p>
                </div>
            </header>


            <?= $message ?>


            <section class="neon-card">
                <div class="card-title"><i class="fa-solid fa-key" style="color:var(--accent-primary);"></i>Pending Requests</div>

                <div class="table-container">

                    <table>
                        <thead>
                            <tr>
                                <th>Employee ID</th>
                                <th>Name</th>
                                <th>Status</th>
                                <th>Action</th>
                            </tr>
                        </thead>

                        <tbody>
                            <?php
                            $res=$conn->query(
                                "
                                SELECT
                                emp_id,
                                name,
                                password_status
                                FROM employees
                                WHERE password_status='Pending'
                                "
                            );

                            if($res->num_rows>0){
                                while($r=$res->fetch_assoc()){
                                    ?>
                                        <tr>
                                            <td>
                                                <?= htmlspecialchars($r['emp_id']) ?>
                                            </td>

                                            <td>
                                                <?= htmlspecialchars($r['name']) ?>
                                            </td>

                                            <td>
                                                <span class="badge pending">Pending</span>
                                            </td>

                                            <td>
                                                <form method="POST" action="password_requests.php" style=" display:flex; gap:10px;">
                                                    <input type="hidden" name="emp" value="<?= $r['emp_id'] ?>">
                                                    <button class="badge approved" name="approve" style="border:none;cursor:pointer;">Approve</button>
                                                    <button class="badge rejected" name="reject" style="border:none;cursor:pointer;">Reject</button>
                                                </form>

                                            </td>

                                        </tr>
                                    <?php
                                }
                            }
                            else{

                                echo "<tr><td colspan='4' style='text-align:center;'>No pending requests.</td></tr>";

                            }

                            ?>

                        </tbody>
                    </table>
                </div>
            </section>
        </main>

        <script>

            function confirmLogout(){

                return confirm("Are you sure you want to log out?");

            }

        </script>
    </body>
</html>