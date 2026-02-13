<?php
session_start();
require_once 'config.php';
require_once 'middleware/auth_middleware.php';
requireTeacher();
// Only Teacher/Admin allowed
if (!isset($_SESSION['role']) || ($_SESSION['role'] != "teacher" && $_SESSION['role'] != "admin")) {
    header("Location: login.php?msg=Access Denied");
    exit();
}

// Get quiz_id
if (!isset($_GET['quiz_id']) || $_GET['quiz_id'] === "") {
    die("❌ Missing quiz ID!");
}

$quiz_id = $_GET['quiz_id'];
$conn = getDBConnection();

// Get quiz details
$quizQuery = mysqli_query($conn, "SELECT title, subject FROM quizzes WHERE quiz_id = '$quiz_id'");
$quizData = mysqli_fetch_assoc($quizQuery);
$quizTitle = $quizData['title'] ?? $quiz_id;
$quizSubject = $quizData['subject'] ?? 'General';

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit'])) {
    $question_text = trim($_POST['question_text'] ?? '');
    $option_a = trim($_POST['option_a'] ?? '');
    $option_b = trim($_POST['option_b'] ?? '');
    $option_c = trim($_POST['option_c'] ?? '');
    $option_d = trim($_POST['option_d'] ?? '');
    $correct_answer = $_POST['correct_answer'] ?? '';

    if ($question_text && $option_a && $option_b && $option_c && $option_d && $correct_answer) {
        $stmt = mysqli_prepare($conn, "
            INSERT INTO questions 
            (quiz_id, question_text, option_a, option_b, option_c, option_d, correct_answer) 
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        mysqli_stmt_bind_param($stmt, "sssssss", 
            $quiz_id, $question_text, $option_a, $option_b, $option_c, $option_d, $correct_answer
        );
        
        if (mysqli_stmt_execute($stmt)) {
            $message = "✅ Question added successfully!";
            // Clear form
            $_POST = [];
        } else {
            $message = "❌ Error adding question: " . mysqli_error($conn);
        }
        mysqli_stmt_close($stmt);
    } else {
        $message = "❌ All fields are required!";
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<title>Add Questions | Quiz System</title>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<style>
:root {
    --primary:#033b6a;
    --accent:#3b8beb;
    --card-bg: rgba(255,255,255,0.96);
    --success: #28a745;
    --warning: #ffc107;
    --danger: #dc3545;
}
*{ box-sizing:border-box; margin:0; padding:0; }
body{
    font-family:"Poppins",Arial,sans-serif;
    min-height:100vh;
    display:flex;
    justify-content:center;
    align-items:center;
    background: linear-gradient(145deg, #044077, #0b64b0);
    padding: 20px;
}

.container{
    width:900px;
    max-width:95%;
    border-radius:25px;
    overflow:hidden;
    background:var(--card-bg);
    box-shadow:0 15px 40px rgba(0,0,0,0.3);
    position:relative;
    z-index:1;
    animation:fadeIn .7s ease;
}
@keyframes fadeIn{
    from{opacity:0; transform:translateY(20px);}
    to{opacity:1; transform:translateY(0);}
}

/* Header */
.header {
    background: linear-gradient(135deg, var(--primary), #0b64b0);
    color: white;
    padding: 25px 40px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 5px solid var(--accent);
}

.header-left h1 {
    font-size: 28px;
    margin-bottom: 5px;
}

.header-left p {
    opacity: 0.9;
    font-size: 14px;
}

.back-btn {
    background: white;
    color: var(--primary);
    padding: 10px 25px;
    border-radius: 10px;
    text-decoration: none;
    font-weight: bold;
    transition: 0.3s;
}

.back-btn:hover {
    background: var(--accent);
    color: white;
    transform: translateY(-2px);
}

/* Main Content */
.main-content {
    display: flex;
    min-height: 500px;
}

/* Left Panel */
.left-panel{
    width:40%;
    background:var(--primary);
    color:#fff;
    padding:35px;
    display:flex;
    flex-direction:column;
    justify-content:center;
    align-items:center;
    text-align:center;
}

.left-panel img{
    width:120px;
    height:120px;
    border-radius:20px;
    object-fit:cover;
    box-shadow:0 6px 20px rgba(0,0,0,0.4);
    border: 3px solid white;
    margin-bottom: 25px;
}

.left-panel h2{ 
    font-size:26px; 
    margin-top:5px;
    margin-bottom:12px;
}

.left-panel p{ 
    font-size:14px;
    opacity:0.9;
    margin-bottom:5px; 
}

/* Right Panel */
.right-panel{
    width:60%;
    padding:35px 40px;
    display:flex;
    flex-direction:column;
    justify-content:center;
}

.right-panel h2{ 
    color:var(--primary); 
    margin-bottom:20px; 
    font-size: 24px;
}

/* Quiz Info */
.quiz-info {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 10px;
    margin-bottom: 20px;
    border-left: 4px solid var(--primary);
}

.quiz-info p {
    margin: 5px 0;
    font-size: 15px;
}

.quiz-info strong {
    color: var(--primary);
}

/* Forms */
form{ display:flex; flex-direction:column; }

label{ 
    font-size:14px; 
    margin-top:12px; 
    color:#222;
    font-weight: 600;
}

input, textarea, select{ 
    width:100%; 
    padding:12px; 
    margin-top:6px; 
    border-radius:10px; 
    border:1px solid #bbb; 
    font-size:15px; 
    transition:.25s; 
    background:white;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

input:focus, textarea:focus, select:focus{ 
    border-color:var(--accent); 
    box-shadow:0 0 10px rgba(59,139,235,0.45); 
    outline: none;
}

/* Options Grid */
.options-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin: 15px 0;
}

/* Buttons */
.btn-group {
    display: flex;
    gap: 15px;
    margin-top: 25px;
}

.btn {
    flex: 1;
    padding:8px 20px; 
    border:none; 
    border-radius:12px;
    color:#fff; 
    cursor:pointer; 
    font-size:14px; 
    transition:.3s;
    font-weight: bold;
    text-align: center;
    text-decoration: none;
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 3px;
}

.btn-primary {
    background:var(--primary); 
}

.btn-primary:hover{ 
    background:var(--accent); 
    transform:translateY(-2px);
    box-shadow: 0 5px 15px rgba(59,139,235,0.3);
}

.btn-success {
    background:var(--success);
}

.btn-success:hover {
    background: #218838;
    transform:translateY(-2px);
}

.btn-secondary {
    background: #6c757d;
}

.btn-secondary:hover {
    background: #5a6268;
    transform:translateY(-2px);
}

/* Messages */
#message {
    margin-bottom:20px; 
    padding:15px; 
    border-radius:10px; 
    font-size:15px;
    border-left: 5px solid;
}

.message-success{ 
    background:#d4edda; 
    color:#155724;
    border-left-color: var(--success);
}
.message-error{ 
    background:#ffefef; 
    color:#b10000;
    border-left-color: var(--danger);
}

/* Navigation */
.nav-links {
    display: flex;
    justify-content: center;
    gap: 20px;
    margin-top: 30px;
    padding-top: 20px;
    border-top: 1px solid #eee;
}

.nav-links a {
    color: var(--primary);
    text-decoration: none;
    font-weight: 500;
    padding: 8px 16px;
    border-radius: 8px;
    transition: all 0.3s;
}

.nav-links a:hover {
    background: #f0f8ff;
    color: var(--accent);
}

@media(max-width:768px){
    .container{ width:95%; }
    .main-content{ flex-direction:column; }
    .left-panel, .right-panel{ width:100%; padding:25px; }
    .header { flex-direction: column; text-align: center; gap: 15px; }
    .options-grid { grid-template-columns: 1fr; }
    .btn-group { flex-direction: column; }
}
</style>
</head>
<body>

<div class="container">
    <!-- Header -->
    <div class="header">
        <div class="header-left">
            <h1>📝 Add Questions</h1>
            <p>Add multiple choice questions to your quiz</p>
        </div>
        <a href="teacher_myquizzes.php" class="back-btn">← My Quizzes</a>
    </div>

    <div class="main-content">
        <!-- Left Panel -->
        <div class="left-panel">
            <img src="icon5.jpeg" alt="Quiz System">
            <h2>Manual Mode</h2>
            <p>Add questions manually</p>
            <p>Fill all fields carefully</p>
            <p>Quiz ID: <?php echo htmlspecialchars($quiz_id); ?></p>
        </div>

        <!-- Right Panel -->
        <div class="right-panel">
            <!-- Quiz Info -->
            <div class="quiz-info">
                <p><strong>Quiz ID:</strong> <?php echo htmlspecialchars($quiz_id); ?></p>
                <p><strong>Title:</strong> <?php echo htmlspecialchars($quizTitle); ?></p>
                <p><strong>Subject:</strong> <?php echo htmlspecialchars($quizSubject); ?></p>
            </div>
            
            <!-- Message Display -->
            <?php if ($message): ?>
                <div id="message" class="<?php echo strpos($message, '✅') !== false ? 'message-success' : 'message-error'; ?>">
                    <?php echo $message; ?>
                </div>
            <?php endif; ?>

            <h2>Add New Question</h2>
            
            <form method="POST">
                <label>Question Text</label>
                <textarea name="question_text" required placeholder="Enter your question here..."><?php echo $_POST['question_text'] ?? ''; ?></textarea>

                <div class="options-grid">
                    <div>
                        <label>Option A</label>
                        <input type="text" name="option_a" value="<?php echo $_POST['option_a'] ?? ''; ?>" required placeholder="Enter option A">
                    </div>
                    
                    <div>
                        <label>Option B</label>
                        <input type="text" name="option_b" value="<?php echo $_POST['option_b'] ?? ''; ?>" required placeholder="Enter option B">
                    </div>
                    
                    <div>
                        <label>Option C</label>
                        <input type="text" name="option_c" value="<?php echo $_POST['option_c'] ?? ''; ?>" required placeholder="Enter option C">
                    </div>
                    
                    <div>
                        <label>Option D</label>
                        <input type="text" name="option_d" value="<?php echo $_POST['option_d'] ?? ''; ?>" required placeholder="Enter option D">
                    </div>
                </div>

                <label>Correct Answer</label>
                <select name="correct_answer" required>
                    <option value="">Select Correct Option</option>
                    <option value="A" <?php echo ($_POST['correct_answer'] ?? '') == 'A' ? 'selected' : ''; ?>>Option A</option>
                    <option value="B" <?php echo ($_POST['correct_answer'] ?? '') == 'B' ? 'selected' : ''; ?>>Option B</option>
                    <option value="C" <?php echo ($_POST['correct_answer'] ?? '') == 'C' ? 'selected' : ''; ?>>Option C</option>
                    <option value="D" <?php echo ($_POST['correct_answer'] ?? '') == 'D' ? 'selected' : ''; ?>>Option D</option>
                </select>

                <div class="btn-group">
                    <button type="submit" name="submit" class="btn btn-primary">
                        <span>➕</span> Add Question
                    </button>
                    <a href="create_quiz.php" class="btn btn-secondary">
                        <span>➕</span> Create New Quiz
                    </a>
                    <a href="view_quiz.php?quiz_id=<?php echo urlencode($quiz_id); ?>" class="btn btn-success">
                        <span>👁️</span> View Quiz
                    </a>
                </div>
            </form>

            <!-- Navigation Links -->
            <div class="nav-links">
                <a href="dashboard.php">🏠 Dashboard</a>
                <a href="teacher_myquizzes.php">📚 My Quizzes</a>
                <a href="logout.php">🚪 Logout</a>
            </div>
        </div>
    </div>
</div>

<script>
// Clear form after successful submission
document.addEventListener('DOMContentLoaded', function() {
    const message = document.getElementById('message');
    if (message && message.textContent.includes('✅')) {
        setTimeout(function() {
            document.querySelector('form').reset();
        }, 1500);
    }
    
    // Focus on first input
    document.querySelector('textarea[name="question_text"]').focus();
});
</script>

</body>
</html>