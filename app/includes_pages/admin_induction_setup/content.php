<?php
session_start();
error_reporting(E_ALL);
ini_set('display_errors', 'Off');
include("../../includes/common.php");


if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'induction_list') {
    $database = new Database();
    $conn = $database->connect();
    try {
        $query = "SELECT * FROM tblInduction WHERE company_id = :company_id";
        $statement = $conn->prepare($query);
        $statement->bindParam(':company_id', $_SESSION['session_company_id']);
        $statement->execute();
        $rowCount = $statement->rowCount();
        if ($rowCount > 0) {
            $output = '<div class="card-body">
						<div class="row">
							<div class="col">
								<h5 class="card-title">Company Induction</h5>
							</div>
							<div class="col-auto mt-2">
									<button type="button" class="btn btn-sm btn-secondary" onclick="AddInductionModal()">Add Induction</button>
							</div>
						</div>
						<div class="table-responsive">
							<table class="table table-hover">
								<thead>
									<tr>
										<th scope="col">Description</th>
										<th scope="col">Description</th>
										<th scope="col">Description</th>
										<th scope="col">Edit</th>
									</tr>
								</thead>
								<tbody>';
            while ($row = $statement->fetch(PDO::FETCH_ASSOC)) {
                $output .= '<tr>';
                $output .= '<td>' . $row['description']. '</td>'; // Adjust based on your actual columns
                $output .= '<td>' . $row['description'] . '</td>'; // Change 'email' to your actual column names
				$output .= '<td>' . $row['description'] . '</td>'; // Change 'email' to your actual column names
                $output .= '<td><button class="btn btn-sm btn-secondary" onclick="EditInduction(' . $row['id'] . ')"><i class="bx bx-edit"></i></button></td>'; 
                $output .= '</tr>';
            }
            $output .= '</tbody></table></div></div></div>';
            echo $output;
        } else {
            echo '<div class="card"><div class="card-body"><p>No vendors found</p></div>
			<div class="col-auto mt-2">
					<button type="button" class="btn btn-sm btn-secondary" onclick="AddVendorModal()">Add Vendor</button>
				</div>
			</div>';
        }
    } catch (PDOException $e) {
        echo '<div class="card"><div class="card-body"><p>Error fetching: ' . $e->getMessage() . '</p></div></div>';
    }
    $conn = null;
    exit; 
}

// Function to check if the selected answer is correct
function isCorrectAnswer($answerId) {
    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT is_correct FROM tblInduction_answers WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$answerId]); // Directly passing value to execute method

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($row) {
        return $row['is_correct'];
    }
    return false;
}
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['tab_id']) && $_POST['tab_id'] == 'edit_induction') {

    // Track the current question ID in a session
    if (!isset($_SESSION['current_question_id'])) {
        $_SESSION['current_question_id'] = 1; // Start with the first question
    } else {
        // Increment the question ID each time the page is loaded with a valid answer
        if (isset($_POST['next']) && isset($_POST['answer']) && isCorrectAnswer($_POST['answer'])) {
            $_SESSION['current_question_id']++;
        }
    }

    $database = new Database();
    $conn = $database->connect();

    $query = "SELECT q.question, a.id as answer_id, a.answer FROM tblInduction_questions q LEFT JOIN tblInduction_answers a ON q.id = a.question_id WHERE q.id = ?";
    $stmt = $conn->prepare($query);
    $stmt->execute([$_SESSION['current_question_id']]);

    // HTML output
    $output = '<div class="container">';
    if ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $output .= '<form method="post" id="questionForm">';
        $output .= '<div class="form-group">';
        $output .= '<label>' . htmlspecialchars($row['question']) . '</label>';

        do {
            $output .= '<div class="form-check">';
            $output .= '<input class="form-check-input" type="radio" name="answer" value="' . $row['answer_id'] . '">';
            $output .= '<label class="form-check-label">' . htmlspecialchars($row['answer']) . '</label>';
            $output .= '</div>';
        } while ($row = $stmt->fetch(PDO::FETCH_ASSOC));

        $output .= '<button type="submit" name="next" class="btn btn-primary">Next</button>';
        $output .= '</div>';
        $output .= '</form>';
    } else {
        $output .= 'No more questions.';
        // Optionally reset session for a restart
        // session_destroy();
    }
    $output .= '</div>';

    echo $output;
}
?>
