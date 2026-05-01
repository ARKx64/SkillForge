-- ============================================================
--  SkillForge - Online Skills Marketplace
--  Database Schema (MySQL)
--  Based on ERD: Users, Skills, Tests, Projects, Applications
-- ============================================================

CREATE DATABASE IF NOT EXISTS skillforge_db
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE skillforge_db;

-- ─────────────────────────────────────────
-- TABLE: users
-- Stores all registered users (freelancers & clients)
-- ─────────────────────────────────────────
CREATE TABLE users (
    user_id      INT AUTO_INCREMENT PRIMARY KEY,
    name         VARCHAR(100)  NOT NULL,
    email        VARCHAR(150)  NOT NULL UNIQUE,
    password     VARCHAR(255)  NOT NULL,          -- bcrypt hashed
    role         ENUM('freelancer','client','both') NOT NULL DEFAULT 'freelancer',
    avatar       VARCHAR(255)  DEFAULT NULL,       -- profile image filename
    bio          TEXT          DEFAULT NULL,
    is_active    TINYINT(1)    NOT NULL DEFAULT 1,
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: skills
-- Master list of available skills on the platform
-- ─────────────────────────────────────────
CREATE TABLE skills (
    skill_id     INT AUTO_INCREMENT PRIMARY KEY,
    skill_name   VARCHAR(100) NOT NULL UNIQUE,
    category     VARCHAR(80)  DEFAULT NULL,        -- e.g. "Development", "Design"
    description  TEXT         DEFAULT NULL,
    icon         VARCHAR(50)  DEFAULT 'code',      -- Font Awesome icon name
    created_at   TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: skill_tests
-- Each skill has exactly one test
-- ─────────────────────────────────────────
CREATE TABLE skill_tests (
    test_id       INT AUTO_INCREMENT PRIMARY KEY,
    skill_id      INT          NOT NULL UNIQUE,
    total_marks   INT          NOT NULL DEFAULT 10,
    passing_marks INT          NOT NULL DEFAULT 6,   -- ≥ 60%
    time_limit    INT          NOT NULL DEFAULT 15,  -- minutes
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_st_skill FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: test_questions
-- MCQ questions for each skill test
-- ─────────────────────────────────────────
CREATE TABLE test_questions (
    question_id   INT AUTO_INCREMENT PRIMARY KEY,
    test_id       INT          NOT NULL,
    question_text TEXT         NOT NULL,
    option_a      VARCHAR(255) NOT NULL,
    option_b      VARCHAR(255) NOT NULL,
    option_c      VARCHAR(255) NOT NULL,
    option_d      VARCHAR(255) NOT NULL,
    correct_ans   ENUM('A','B','C','D') NOT NULL,
    marks         INT          NOT NULL DEFAULT 1,
    CONSTRAINT fk_tq_test FOREIGN KEY (test_id) REFERENCES skill_tests(test_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: user_skills
-- Junction table: which skills a user has enlisted + verification status
-- ─────────────────────────────────────────
CREATE TABLE user_skills (
    user_skill_id       INT AUTO_INCREMENT PRIMARY KEY,
    user_id             INT  NOT NULL,
    skill_id            INT  NOT NULL,
    verification_status ENUM('Pending','Verified','Failed') NOT NULL DEFAULT 'Pending',
    enlisted_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_user_skill (user_id, skill_id),
    CONSTRAINT fk_us_user  FOREIGN KEY (user_id)  REFERENCES users(user_id)  ON DELETE CASCADE,
    CONSTRAINT fk_us_skill FOREIGN KEY (skill_id) REFERENCES skills(skill_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: test_results
-- Records every test attempt by a user
-- ─────────────────────────────────────────
CREATE TABLE test_results (
    result_id   INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT  NOT NULL,
    test_id     INT  NOT NULL,
    score       INT  NOT NULL DEFAULT 0,
    status      ENUM('Pass','Fail') NOT NULL,
    attempt_no  INT  NOT NULL DEFAULT 1,
    taken_at    TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_tr_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_tr_test FOREIGN KEY (test_id) REFERENCES skill_tests(test_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: projects
-- Posted by clients; freelancers apply
-- ─────────────────────────────────────────
CREATE TABLE projects (
    project_id   INT AUTO_INCREMENT PRIMARY KEY,
    client_id    INT           NOT NULL,           -- FK → users(user_id)
    title        VARCHAR(200)  NOT NULL,
    description  TEXT          NOT NULL,
    budget       DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    deadline     DATE          NOT NULL,
    required_skill_id INT      DEFAULT NULL,       -- FK → skills(skill_id)
    status       ENUM('Open','In Progress','Closed') NOT NULL DEFAULT 'Open',
    created_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at   TIMESTAMP     NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    CONSTRAINT fk_proj_client FOREIGN KEY (client_id) REFERENCES users(user_id) ON DELETE CASCADE,
    CONSTRAINT fk_proj_skill  FOREIGN KEY (required_skill_id) REFERENCES skills(skill_id) ON DELETE SET NULL
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: applications
-- Freelancers apply to projects
-- ─────────────────────────────────────────
CREATE TABLE applications (
    application_id INT AUTO_INCREMENT PRIMARY KEY,
    project_id     INT  NOT NULL,
    user_id        INT  NOT NULL,
    cover_letter   TEXT DEFAULT NULL,
    status         ENUM('Applied','Accepted','Rejected') NOT NULL DEFAULT 'Applied',
    applied_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at     TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uq_application (project_id, user_id),
    CONSTRAINT fk_app_project FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    CONSTRAINT fk_app_user    FOREIGN KEY (user_id)    REFERENCES users(user_id)    ON DELETE CASCADE
) ENGINE=InnoDB;

-- ─────────────────────────────────────────
-- TABLE: notifications
-- System notifications for users
-- ─────────────────────────────────────────
CREATE TABLE notifications (
    notif_id    INT AUTO_INCREMENT PRIMARY KEY,
    user_id     INT          NOT NULL,
    message     VARCHAR(500) NOT NULL,
    type        ENUM('info','success','warning','danger') NOT NULL DEFAULT 'info',
    is_read     TINYINT(1)   NOT NULL DEFAULT 0,
    created_at  TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_notif_user FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  SEED DATA
-- ============================================================

-- Skills
INSERT INTO skills (skill_name, category, description, icon) VALUES
('Frontend Development', 'Development', 'HTML, CSS, JavaScript, React, Vue', 'laptop-code'),
('Backend Development',  'Development', 'PHP, Node.js, Python, APIs, Databases', 'server'),
('Python Development',   'Development', 'Python scripting, Django, Flask, ML basics', 'python'),
('AI & ML Expert',       'AI/ML',       'Machine Learning, Deep Learning, NLP', 'brain'),
('UI/UX Design',         'Design',      'Figma, Adobe XD, Wireframing, Prototyping', 'palette'),
('Mobile Development',   'Development', 'React Native, Flutter, iOS, Android', 'mobile-alt'),
('Database Design',      'Data',        'MySQL, PostgreSQL, MongoDB, Redis', 'database'),
('DevOps & Cloud',       'Infrastructure','Docker, Kubernetes, AWS, CI/CD pipelines', 'cloud'),
('Graphic Design',       'Design',      'Photoshop, Illustrator, Branding, Logo Design', 'paint-brush'),
('Content Writing',      'Creative',    'Blog posts, Copywriting, SEO content', 'pen-nib');

-- Skill Tests (10 questions each, pass = 6/10)
INSERT INTO skill_tests (skill_id, total_marks, passing_marks, time_limit) VALUES
(1, 10, 6, 15),
(2, 10, 6, 15),
(3, 10, 6, 15),
(4, 10, 6, 15),
(5, 10, 6, 15),
(6, 10, 6, 15),
(7, 10, 6, 15),
(8, 10, 6, 15),
(9, 10, 6, 15),
(10, 10, 6, 15);

-- Frontend Dev Questions (test_id = 1)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(1,'Which HTML tag is used to define an internal stylesheet?','<script>','<css>','<style>','<link>','C'),
(1,'What does CSS stand for?','Creative Style Sheets','Cascading Style Sheets','Computer Style Sheets','Colorful Style Sheets','B'),
(1,'Which JavaScript method selects an element by ID?','getElement()','querySelector()','getElementById()','findById()','C'),
(1,'What is the correct way to declare a variable in modern JavaScript?','var x = 5','let x = 5','variable x = 5','int x = 5','B'),
(1,'Which CSS property is used to make a flex container?','display: flex','display: grid','float: left','position: flex','A'),
(1,'What does the DOM stand for?','Document Object Model','Data Object Model','Document Orientation Method','Data Oriented Module','A'),
(1,'Which HTML attribute specifies an alternate text for an image?','title','alt','src','href','B'),
(1,'How do you add a comment in JavaScript?','/* comment */','// comment','# comment','<!-- comment -->','B'),
(1,'What is the default value of the position property in CSS?','relative','fixed','absolute','static','D'),
(1,'Which event fires when the user clicks on an HTML element?','onhover','onchange','onclick','onfocus','C'),
(1,'Which CSS property changes text color?','font-color','color','text-style','background-color','B'),
(1,'Which HTML element is used for the largest heading?','<heading>','<h6>','<head>','<h1>','D'),
(1,'What does JSON stand for?','Java Source Object Notation','JavaScript Object Notation','Joined Standard Object Naming','Java Structured Output Network','B'),
(1,'Which JavaScript array method adds an item to the end?','append()','push()','add()','merge()','B'),
(1,'Which CSS unit is relative to the root font size?','px','vh','rem','cm','C'),
(1,'Which HTML tag is used to create a hyperlink?','<a>','<link>','<href>','<url>','A'),
(1,'What is the purpose of media queries in CSS?','To connect CSS files','To apply styles based on device conditions','To compress images','To validate HTML','B'),
(1,'Which method converts a JSON string into a JavaScript object?','JSON.make()','JSON.parse()','JSON.stringify()','JSON.object()','B'),
(1,'Which CSS property controls the space inside an element border?','margin','gap','padding','spacing','C'),
(1,'Which HTML tag is used for unordered lists?','<ul>','<ol>','<li>','<list>','A'),
(1,'Which JavaScript keyword declares a constant variable?','let','var','static','const','D'),
(1,'What does responsive web design improve?','Database speed','Layout adaptability across devices','Server security','File compression','B'),
(1,'Which selector targets an element with class hero?','#hero','hero','.hero','*hero','C'),
(1,'Which CSS layout system is one-dimensional?','Grid','Flexbox','Table','Float','B'),
(1,'Which HTML input type hides typed characters?','text','hidden','password','secure','C'),
(1,'Which JavaScript method attaches an event listener?','listenEvent()','onEvent()','addEventListener()','bindEvent()','C'),
(1,'Which HTML tag embeds JavaScript code directly in a page?','<js>','<script>','<javascript>','<code>','B'),
(1,'Which CSS property rounds element corners?','border-style','corner-radius','border-radius','radius','C'),
(1,'What does the viewport meta tag help with?','Database indexing','Responsive scaling on devices','Audio playback','SEO only','B'),
(1,'Which JavaScript comparison checks both value and type?','==','=','!=','===','D');

-- Backend Dev Questions (test_id = 2)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(2,'What does PHP stand for?','Personal Home Page','Hypertext Preprocessor','Public HTML Page','PHP Hypertext Preprocessor','D'),
(2,'Which superglobal in PHP holds form data sent via POST?','$_GET','$_POST','$_REQUEST','$_FORM','B'),
(2,'What function is used to hash passwords in PHP?','md5()','sha1()','password_hash()','encrypt()','C'),
(2,'What is REST?','A database technology','Representational State Transfer','Remote Execution Service Transfer','A caching method','B'),
(2,'Which HTTP status code means "Not Found"?','200','301','404','500','C'),
(2,'What does SQL stand for?','Structured Query Language','Simple Query Language','Standard Query Language','Sequential Query Language','A'),
(2,'Which PHP function starts a session?','start_session()','session_begin()','session_start()','begin_session()','C'),
(2,'What is the purpose of an API?','To style web pages','To allow different software to communicate','To store data','To compile code','B'),
(2,'Which HTTP method is typically used to create a new resource?','GET','PUT','DELETE','POST','D'),
(2,'What does MVC stand for?','Model View Controller','Main View Component','Module Variable Class','Model Variable Component','A'),
(2,'Which PHP symbol is used before variables?','#','$','@','%','B'),
(2,'Which function outputs text in PHP?','printLine()','echo()','write()','show()','B'),
(2,'What does CRUD stand for?','Create Read Update Delete','Copy Run Upload Download','Create Remove Undo Deploy','Cache Restore Update Debug','A'),
(2,'Which HTTP method is mainly used to fetch data?','PATCH','POST','GET','DELETE','C'),
(2,'Which PHP function checks whether a form field is set?','exists()','isset()','empty()','defined()','B'),
(2,'What is middleware commonly used for?','Styling pages','Handling request processing such as auth or logging','Creating tables','Compressing images','B'),
(2,'Which PHP extension is used in this project for database access?','mysqli only','PDO','curl','gd','B'),
(2,'What is the default port of MySQL?','80','21','3306','5432','C'),
(2,'Which status code usually indicates a successful request?','201 only','404','500','200','D'),
(2,'What is server-side validation?','Validation done in CSS','Validation performed in the browser only','Validation performed on the backend before processing data','Validation stored in cookies','C'),
(2,'Which PHP function encodes data as JSON?','json_encode()','json_decode()','array_to_json()','encode_json()','A'),
(2,'Which superglobal contains URL query parameters?','$_POST','$_SESSION','$_GET','$_SERVER','C'),
(2,'What is authentication?','Checking user identity','Formatting database tables','Improving performance','Uploading files','A'),
(2,'What is authorization?','Verifying identity','Deciding what an authenticated user is allowed to do','Encrypting passwords','Rendering views','B'),
(2,'Which SQL statement adds a new row into a table?','INSERT','ALTER','CREATE','TRUNCATE','A'),
(2,'Why are prepared statements important?','They make CSS responsive','They help prevent SQL injection','They increase image quality','They replace sessions','B'),
(2,'Which PHP function ends script execution immediately?','break','stop()','exit()','returnAll()','C'),
(2,'What does an API response in JSON make easier?','Database backups','Communication between frontend and backend','Image editing','Manual testing only','B'),
(2,'Which HTTP method is often used to update an entire resource?','PUT','GET','TRACE','HEAD','A'),
(2,'What is hashing used for in passwords?','Reversible encryption for easy retrieval','Storing passwords in a safer non-plain-text form','Compressing user data','Creating sessions automatically','B');

-- Python Questions (test_id = 3)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(3,'What is the output of print(type([]))?','<class tuple>','<class list>','<class array>','<class dict>','B'),
(3,'Which keyword is used to define a function in Python?','function','def','fun','define','B'),
(3,'What is the correct file extension for Python files?','.pt','.py','.pyt','.python','B'),
(3,'How do you start a comment in Python?','//','/*','#','--','C'),
(3,'Which method adds an item to the end of a list?','add()','append()','insert()','push()','B'),
(3,'What does len() do?','Deletes list','Reverses string','Returns length','Converts to int','C'),
(3,'What is a lambda in Python?','A module','A loop','An anonymous function','A class method','C'),
(3,'How do you import a module in Python?','require module','#include module','import module','use module','C'),
(3,'What does pip stand for?','Python Install Package','Pip Installs Packages','Package Install Python','Program Install Pip','B'),
(3,'Which data type is immutable in Python?','list','dict','tuple','set','C'),
(3,'Which built-in type stores key-value pairs in Python?','list','tuple','dict','set','C'),
(3,'Which keyword is used for a loop over a sequence?','foreach','loop','for','iterate','C'),
(3,'What does str.lower() do?','Converts a string to lowercase','Deletes a string','Counts letters','Splits a string','A'),
(3,'Which operator is used for exponentiation in Python?','^','**','//','%%','B'),
(3,'What is the output type of input() in Python?','int','float','str','bool','C'),
(3,'Which statement handles exceptions?','catch','handle','try','guard','C'),
(3,'How do you create an empty dictionary?','[]','()','{}','set()','C'),
(3,'Which keyword is used to create a class?','object','class','struct','define','B'),
(3,'Which method removes and returns the last list item by default?','delete()','remove()','pop()','discard()','C'),
(3,'What does range(3) produce?','0,1,2','1,2,3','0,1,2,3','3 only','A'),
(3,'Which file mode is used to read a file?','w','a','x','r','D'),
(3,'What does bool(0) return?','True','False','0','None','B'),
(3,'Which collection does not allow duplicate elements?','list','dict','set','tuple','C'),
(3,'What is inheritance in Python?','A way for a class to use another class properties and methods','A package manager','A database model','A debugging tool','A'),
(3,'Which keyword returns a value from a function?','yield','break','return','pass','C'),
(3,'Which module is commonly used for working with JSON?','json','csv','math','randomjson','A'),
(3,'What does list slicing my_list[1:3] include?','Indexes 1 and 2','Indexes 1,2,3','Only index 3','All items','A'),
(3,'Which statement skips the rest of the current loop iteration?','stop','pass','continue','redo','C'),
(3,'What does __init__ usually do in a class?','Deletes the object','Initializes object attributes','Imports modules','Prints class name','B'),
(3,'Which keyword creates an anonymous function?','lambda','anon','func','map','A');

-- AI/ML Questions (test_id = 4)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(4,'What does ML stand for?','Model Learning','Machine Language','Machine Learning','Multilayer Logic','C'),
(4,'Which type of learning uses labeled data?','Unsupervised','Reinforcement','Semi-supervised','Supervised','D'),
(4,'What is overfitting?','Model too simple','Model performs well on training but poor on new data','Model has no parameters','Model trained too fast','B'),
(4,'What does CNN stand for in deep learning?','Convolutional Neural Network','Connected Node Network','Computed Neuron Net','Cascaded Network Node','A'),
(4,'Which library is commonly used for ML in Python?','NumPy only','Pandas only','scikit-learn','Flask','C'),
(4,'What is a neural network?','A database system','A computing system inspired by the brain','A network protocol','A graphics engine','B'),
(4,'What does NLP stand for?','Natural Language Processing','Network Layer Protocol','Neuron Loop Process','Node Logic Pathway','A'),
(4,'What is the role of a loss function?','Initialize weights','Measure error between prediction and actual','Store model data','Plot graphs','B'),
(4,'Which activation function outputs values between 0 and 1?','ReLU','Tanh','Sigmoid','Softmax','C'),
(4,'What is gradient descent?','A data cleaning step','An optimization algorithm','A neural network type','A regularization method','B'),
(4,'What is a dataset in machine learning?','A color palette','A collection of data used for analysis or training','A model type','A loss function','B'),
(4,'Which learning method learns by rewards and penalties?','Supervised learning','Transfer learning','Reinforcement learning','Batch learning','C'),
(4,'What is a feature in ML?','The final prediction','An input variable used by the model','A chart title','A type of GPU','B'),
(4,'What is a label in supervised learning?','The target output','A hidden layer','A file name','A training server','A'),
(4,'Which metric is commonly used for classification accuracy?','MAE','Accuracy','RMSE','R-squared','B'),
(4,'What does training a model mean?','Deploying it to production','Learning patterns from data','Drawing a chart','Collecting only images','B'),
(4,'What is a test set used for?','Model training only','Hyperparameter naming','Evaluating performance on unseen data','Data cleaning','C'),
(4,'What is precision in classification?','Correct positive predictions out of predicted positives','All correct predictions','Training speed','Error rate','A'),
(4,'What is recall in classification?','Correct positive predictions out of actual positives','The same as loss','Number of epochs','Validation split','A'),
(4,'What does unsupervised learning work with?','Only labeled data','No input data','Mostly unlabeled data','Only images','C'),
(4,'What is clustering?','Sorting by file size','Grouping similar data points together','Removing labels','Increasing model depth','B'),
(4,'Which algorithm is commonly used for classification?','Linear regression only','K-nearest neighbors','Apriori','PCA only','B'),
(4,'What is an epoch?','One full pass through the training dataset','A type of activation function','A database transaction','A model export file','A'),
(4,'What does feature scaling help with?','Making file names shorter','Improving training behavior for some algorithms','Adding more labels','Creating dashboards','B'),
(4,'What is bias in model terms?','A systematic error from assumptions in the model','A chart color','A random password','A database key','A'),
(4,'What does validation data help with?','Choosing model settings before final testing','Replacing the training set','Deleting noisy data automatically','Writing APIs','A'),
(4,'Which library is well known for deep learning?','Bootstrap','TensorFlow','jQuery','Laravel','B'),
(4,'What is a confusion matrix used for?','Compressing datasets','Summarizing classification results','Creating animations','Storing models','B'),
(4,'What is dimensionality reduction?','Adding more features','Reducing the number of input variables','Increasing epochs','Changing labels','B'),
(4,'Why is data preprocessing important?','It only changes font size','It improves data quality before training','It replaces evaluation','It removes the need for models','B');

-- UI/UX Questions (test_id = 5)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(5,'What does UX stand for?','User Experience','Unique Execution','Universal Exchange','User Exchange','A'),
(5,'Which tool is most popular for UI design prototyping?','Photoshop','Figma','Excel','Notepad','B'),
(5,'What is a wireframe?','A final polished design','A code structure','A basic layout sketch','A color palette','C'),
(5,'What does accessibility mean in design?','Making sites fast','Making sites usable by everyone including disabled users','Making sites colorful','Making sites expensive','B'),
(5,'What is a design system?','A collection of reusable components and guidelines','A programming language','A testing method','A server setup','A'),
(5,'What does the term "responsive design" mean?','Design that changes color','Design adapting to different screen sizes','Design that loads fast','Design with animations','B'),
(5,'What is a persona in UX?','A login page','A fictional user representing target audience','A design template','A color theme','B'),
(5,'What is the 60-30-10 rule in design?','Font sizes','Color distribution rule','Layout columns','Grid spacing','B'),
(5,'What does CTA stand for in UI?','Click To Animate','Call To Action','Create Text Area','Color Theme Adjustment','B'),
(5,'What is white space in design?','Blank paper','Empty area around elements','White color usage','Background color','B'),
(5,'What is UI in digital products?','User Interface','Universal Interaction','User Integration','Usability Index','A'),
(5,'Which design principle improves readability?','Low contrast text','Consistent spacing and hierarchy','Tiny font sizes','Random alignment','B'),
(5,'What is a prototype?','A final coded product only','An interactive model of a design','A database schema','A CSS reset','B'),
(5,'What does usability focus on?','How easy a product is to use','How expensive a design is','How many animations it has','How many colors it uses','A'),
(5,'Which is an example of visual hierarchy?','Making every element identical','Using size and contrast to guide attention','Removing headings','Disabling buttons','B'),
(5,'What is onboarding in UX?','Deleting user accounts','Helping new users learn the product','Only login styling','Server deployment','B'),
(5,'Why are design tokens useful?','They replace testing','They store reusable visual values like colors and spacing','They generate databases','They host images','B'),
(5,'What is consistency in UI design?','Changing layouts on every page','Using similar patterns and components throughout the product','Using only one color','Avoiding typography','B'),
(5,'What is affordance in UX?','An element suggesting how it should be used','A hosting service','A page loading metric','A typeface family','A'),
(5,'Why is user feedback important in interfaces?','It increases server RAM','It tells users what happened after an action','It replaces accessibility','It removes the need for buttons','B'),
(5,'Which color contrast practice is best for accessibility?','Low contrast pastel text','Strong readable contrast between text and background','Only white backgrounds','Only black buttons','B'),
(5,'What is a user journey map?','A server log','A visualization of steps users take to achieve a goal','A CSS grid','A font pairing sheet','B'),
(5,'What is an empty state?','A broken page','A designed screen shown when there is no data yet','A deleted database','A login failure','B'),
(5,'What is heuristic evaluation?','A method of reviewing a design using usability principles','A color export tool','A photo editing technique','A hosting dashboard','A'),
(5,'Why are labels important on forms?','They slow down pages','They improve clarity and accessibility','They replace placeholders','They hide errors','B'),
(5,'What does mobile-first design mean?','Designing only for phones','Starting with small screens before scaling up','Using larger images first','Ignoring desktop users','B'),
(5,'Which layout tool is useful for alignment?','A randomizer','A grid system','A spell checker','A package manager','B'),
(5,'What is a dark pattern?','A helpful onboarding method','A manipulative design choice that tricks users','A dark mode theme','A contrast guideline','B'),
(5,'What is information architecture?','Organizing content and navigation logically','Compressing images','Writing CSS variables','Configuring servers','A'),
(5,'What is a microcopy?','Small text like button labels and helper messages','A tiny image','A database field','A logo animation','A');

-- Mobile Dev Questions (test_id = 6)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(6,'What is React Native used for?','Server-side scripting','Building mobile apps using JavaScript','Database design','Network configuration','B'),
(6,'Which language is primarily used for iOS development?','Java','Kotlin','Swift','C#','C'),
(6,'What is Flutter built with?','JavaScript','Dart','Python','Go','B'),
(6,'What does APK stand for?','Apple Package Kit','Android Package Kit','Application Package Key','Android Program Kit','B'),
(6,'What is the App Store for?','Android apps','Windows apps','iOS apps','Linux apps','C'),
(6,'Which company owns Android?','Apple','Microsoft','Google','Samsung','C'),
(6,'What is a SDK?','Server Development Kit','Software Development Kit','System Design Kit','Standard Debug Kit','B'),
(6,'What is responsive mobile design?','Fixed pixel design','Design that adapts to screen size','Only for tablets','Only for phones','B'),
(6,'What does API integration mean in mobile?','Storing data locally','Connecting app to external services','Designing app icons','Testing on devices','B'),
(6,'What is push notification?','A UI button style','Message sent to device even when app is closed','A network protocol','A storage method','B'),
(6,'Which store distributes Android applications officially?','Play Store','App Store','Galaxy only','Chrome Web Store','A'),
(6,'What is Kotlin mainly associated with?','Android development','iOS development only','Database modeling','Cloud security only','A'),
(6,'What is SwiftUI used for?','Designing iOS interfaces','Container deployment','MySQL backups','Game physics only','A'),
(6,'What does responsive layout help with in mobile apps?','Using one fixed resolution','Adapting UI to different device sizes','Increasing battery drain','Removing navigation','B'),
(6,'What is an emulator in mobile development?','A physical phone','A tool that simulates a mobile device','A database table','A payment gateway','B'),
(6,'Why are permissions important in mobile apps?','They change colors','They control access to device features like camera or location','They increase CPU speed','They replace login','B'),
(6,'What is a native mobile app?','An app built specifically for one platform using its main tools','An app stored in the cloud only','A database plugin','A website theme','A'),
(6,'What is cross-platform development?','Writing one codebase for multiple platforms','Building apps without testing','Only targeting Android','Only using HTML email templates','A'),
(6,'Which file format is used for Android app bundles?','IPA','AAB','EXE','DMG','B'),
(6,'What is an IPA file associated with?','Android builds','iOS app packaging','Database exports','API logs','B'),
(6,'Why is offline storage useful in mobile apps?','It removes all network needs forever','It lets apps keep data locally when connectivity is limited','It replaces authentication','It disables APIs','B'),
(6,'What does app lifecycle refer to?','The pricing plan only','The states an app goes through such as launch, background, and close','The app icon design','Database migrations','B'),
(6,'What is gesture navigation?','Only typing commands','Interacting using swipes, taps, and pinches','A payment method','An IDE shortcut','B'),
(6,'What is a responsive image asset for mobile?','A low-quality image only','An image prepared for different screen densities','A database backup','An APK key','B'),
(6,'Why is testing on real devices important?','It is not important','It helps catch device-specific behavior and performance issues','It replaces emulators completely','It is only for games','B'),
(6,'What is Firebase often used for in mobile apps?','Local CSS styling','Backend services like auth, analytics, and push notifications','Operating system updates','Image compression only','B'),
(6,'What does deep linking allow?','Opening a specific screen from a link','Increasing build time','Removing routes','Encrypting passwords','A'),
(6,'What is a widget in mobile UI?','A reusable interface component','A SQL query','A compiler flag','A hosting plan','A'),
(6,'Why should tap targets be large enough?','To waste space','To improve usability and accessibility on touch screens','To reduce performance','To prevent login','B'),
(6,'What is app versioning used for?','Changing icons only','Tracking releases and updates over time','Deleting old code automatically','Compressing assets','B');
-- Database Questions (test_id = 7)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(7,'What does DBMS stand for?','Data Backup Management System','Database Management System','Data Base Modification System','Digital Base Memory System','B'),
(7,'What is a primary key?','Any column in a table','A unique identifier for each row','A foreign key reference','An index column','B'),
(7,'What is normalization?','Adding more tables','Organizing data to reduce redundancy','Removing all constraints','Backing up data','B'),
(7,'Which SQL command retrieves data?','INSERT','UPDATE','SELECT','DELETE','C'),
(7,'What is a JOIN in SQL?','Combining rows from multiple tables','Deleting duplicate rows','Adding new columns','Creating indexes','A'),
(7,'What does ACID stand for in databases?','Atomicity Consistency Isolation Durability','Add Create Insert Delete','Atomic Coded Integrated Data','None of the above','A'),
(7,'What is an index in a database?','A column type','A data structure for faster queries','A backup mechanism','A table relationship','B'),
(7,'What is a foreign key?','Primary key of same table','Key that references primary key of another table','Any unique column','An encrypted key','B'),
(7,'What does DDL stand for?','Data Definition Language','Data Delete Logic','Database Design Layer','Data Distribution Link','A'),
(7,'Which is a NoSQL database?','MySQL','PostgreSQL','MongoDB','Oracle','C'),
(7,'Which SQL clause is used to filter rows?','ORDER BY','GROUP BY','WHERE','HAVING ONLY','C'),
(7,'Which normal form removes repeating groups?','First Normal Form','Second Normal Form','Third Normal Form','BCNF only','A'),
(7,'What is a composite key?','A key made from multiple columns','A key stored as JSON','A foreign key with text only','A deleted key','A'),
(7,'Which command changes existing table structure?','ALTER TABLE','DROP TABLE','RENAME DATABASE','MERGE TABLE','A'),
(7,'What does COUNT(*) return?','The sum of a column','The total number of rows','The first row only','The largest value','B'),
(7,'Which join returns matched rows from both tables?','INNER JOIN','RIGHT JOIN only','CROSS JOIN','OUTER DELETE','A'),
(7,'What is denormalization?','Removing all data','Adding controlled redundancy for performance or convenience','Deleting foreign keys only','Encrypting tables','B'),
(7,'What is a transaction in databases?','A font setting','A unit of work that should complete fully or not at all','A chart export','A CSS animation','B'),
(7,'What does COMMIT do?','Deletes a table','Saves transaction changes permanently','Creates an index','Starts a server','B'),
(7,'What does ROLLBACK do?','Reverses uncommitted transaction changes','Creates a backup file','Adds a new row','Optimizes queries','A'),
(7,'Which object can improve query performance on searched columns?','Trigger','Index','View','Procedure comment','B'),
(7,'What is a view in SQL?','A stored query presented like a virtual table','A hardware monitor','A password hash','A network tunnel','A'),
(7,'What is cardinality in database design?','The number of rows only','The relationship count between entities','A security policy','A backup interval','B'),
(7,'Which constraint ensures all values in a column are different?','CHECK','UNIQUE','DEFAULT','INDEX','B'),
(7,'What does NOT NULL enforce?','Values must be text','A column cannot store NULL values','A column must be indexed','A row must be deleted','B'),
(7,'What is referential integrity?','Keeping file names short','Ensuring foreign key relationships stay valid','Encrypting records','Sorting rows alphabetically','B'),
(7,'Which SQL function returns the average value?','TOTAL()','MEAN()','AVG()','MID()','C'),
(7,'What does GROUP BY do?','Deletes duplicates','Groups rows sharing the same value for aggregation','Creates backups','Filters before SELECT','B'),
(7,'What is a stored procedure?','A saved image','A reusable set of SQL statements stored in the database','A CSS component','A cache file','B'),
(7,'What is query optimization?','Making queries more efficient','Adding more columns','Disabling indexes','Deleting logs','A');

-- DevOps Questions (test_id = 8)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(8,'What does CI/CD stand for?','Continuous Integration/Continuous Delivery','Cloud Infrastructure/Cloud Deployment','Code Inspection/Code Delivery','Container Integration/Container Deployment','A'),
(8,'What is Docker used for?','Database management','Containerizing applications','Network monitoring','Code editing','B'),
(8,'What is Kubernetes?','A programming language','A database system','A container orchestration platform','A text editor','C'),
(8,'What does AWS stand for?','Advanced Web Software','Amazon Web Services','Automated Web System','Application Web Stack','B'),
(8,'What is a load balancer?','A database tool','Distributes incoming network traffic across servers','A code compiler','A testing framework','B'),
(8,'What is version control?','A software pricing model','System tracking changes in code over time','A deployment method','A server type','B'),
(8,'What is Git?','A programming language','A database','A distributed version control system','A cloud platform','C'),
(8,'What does IaC stand for?','Infrastructure as Code','Internet as Cloud','Integrated Application Control','Internal API Configuration','A'),
(8,'What is a microservices architecture?','Monolithic application design','Application built as small independent services','A frontend framework','A database design pattern','B'),
(8,'What does SLA stand for?','Software Layer Architecture','Server Load Algorithm','Service Level Agreement','System Log Analysis','C'),
(8,'What is a container?','A lightweight package containing an application and its dependencies','A database row','A CSS component','A network cable','A'),
(8,'What is a deployment pipeline?','A database backup plan','An automated sequence for building, testing, and releasing software','A UI pattern','A DNS record','B'),
(8,'Why is monitoring important in production?','It changes source code','It helps detect issues, usage patterns, and outages','It replaces testing','It compresses images','B'),
(8,'What is uptime?','How long a service stays available','The number of commits','A password policy','A version number','A'),
(8,'What is a rollback in deployment?','A way to return to a previous stable release','A database join','A CSS reset','A code formatter','A'),
(8,'What does a package registry store?','Only screenshots','Published software packages and versions','Live databases','Fonts only','B'),
(8,'What is environment parity?','Using similar environments for development, staging, and production','Keeping only one server','Using different tools everywhere','Avoiding testing','A'),
(8,'Why use environment variables?','To style web pages','To keep configuration separate from code','To generate SQL schemas','To replace APIs','B'),
(8,'What is blue-green deployment?','A release strategy using two production environments','A frontend color theme','A CSS testing method','A database model','A'),
(8,'What does logging help with?','Choosing fonts','Troubleshooting and auditing application behavior','Deleting code','Compiling Docker images','B'),
(8,'What is autoscaling?','Manual resizing of images','Automatically adjusting resources based on load','A Git merge strategy','A table constraint','B'),
(8,'What is a secret manager used for?','Storing sensitive values like API keys securely','Compressing builds','Hosting images','Creating logos','A'),
(8,'What is a health check endpoint for?','Testing colors','Checking whether an application is running correctly','Designing forms','Generating PDFs','B'),
(8,'Why are backups critical?','They improve CSS specificity','They help recover data after failures or mistakes','They replace monitoring','They compile code','B'),
(8,'What is a reverse proxy?','A server that forwards client requests to backend services','A Git plugin','A UI pattern','A local CSS file','A'),
(8,'What is infrastructure provisioning?','Setting up servers and services needed for applications','Writing blog content','Designing logos','Training neural networks','A'),
(8,'What is a build artifact?','A packaged output produced by a build process','A database row','A meeting note','A cloud region','A'),
(8,'What is observability?','The ability to understand system behavior through logs, metrics, and traces','A database constraint','A CSS toolkit','A user role','A'),
(8,'What is canary deployment?','Releasing to a small set of users first','A password algorithm','A photo format','A database index','A'),
(8,'Why is automation valuable in DevOps?','It reduces repeatable manual work and errors','It removes the need for code','It disables testing','It only helps designers','A');

-- Graphic Design Questions (test_id = 9)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(9,'What does RGB stand for?','Red Green Blue','Red Grey Black','Real Graphic Base','Range Gradient Blend','A'),
(9,'What is a vector graphic?','Pixel-based image','Resolution-independent image using math','Photograph','Scanned image','B'),
(9,'What file format preserves layers in Photoshop?','.jpg','.png','.psd','.gif','C'),
(9,'What is kerning?','Spacing between lines','Spacing between individual characters','Font weight','Text alignment','B'),
(9,'What does CMYK stand for?','Cyan Magenta Yellow Key','Color Mode Yellow Key','Cyan Model Yellow Kind','Color Mix Yellow Kite','A'),
(9,'What is a mood board?','A feedback tool','Collection of visual references for a design direction','A color picker','A font catalog','B'),
(9,'What is negative space?','Dark colors','Empty area around main subject','Background color','Shadow effect','B'),
(9,'What software is used for vector illustration?','Photoshop','Premiere','Illustrator','Lightroom','C'),
(9,'What is a logo mark?','Text-only logo','Symbol/icon part of a logo','Full logo with text','Watermark','B'),
(9,'What is typography?','Image editing','The art of arranging type/text','Color theory','Layout design','B'),
(9,'What does DPI refer to in design?','Dots per inch','Depth per image','Digital pixel index','Dual print input','A'),
(9,'Which format is best for logos that need scaling?','JPEG','BMP','Vector formats like SVG or AI','GIF','C'),
(9,'What is contrast in design?','Using identical elements','Difference that creates visual interest and clarity','Only using black','Removing white space','B'),
(9,'What is alignment used for?','Random placement','Creating order and structure in layouts','Increasing file size','Adding filters','B'),
(9,'What is a raster image made of?','Mathematical paths only','Pixels','Database rows','Typography grids','B'),
(9,'What is branding?','Only designing a logo','Creating a consistent identity for a company or product','A camera setting','A print layout tool','B'),
(9,'Which principle repeats visual elements for consistency?','Repetition','Isolation','Deletion','Rotation','A'),
(9,'What is opacity in graphics?','Image sharpness','Transparency level','File format','Print resolution','B'),
(9,'What is hierarchy in graphic design?','Making all elements equal','Guiding attention using size, color, and placement','Only centering text','Using one font only','B'),
(9,'What is a mockup?','A presentation of how a design will look in context','A coding language','A backup copy','A type of lens','A'),
(9,'Which color model is mainly used for digital screens?','CMYK','RGB','Pantone only','HSL only','B'),
(9,'What does bleed mean in print design?','Extra area beyond trim edge to avoid white borders','A font effect','A blur filter','A logo variant','A'),
(9,'What is leading in typography?','Spacing between lines of text','Spacing between letters','Font width','Text color','A'),
(9,'Why use a grid in layout design?','To make designs random','To align content consistently','To increase file size','To remove margins','B'),
(9,'What is a brand guideline?','A document defining consistent brand usage','A plugin installer','A print invoice','A code linter','A'),
(9,'Which tool is commonly used for photo editing?','Illustrator','Photoshop','After Effects only','Excel','B'),
(9,'What is saturation?','Color intensity','Image crop size','Page padding','Stroke width','A'),
(9,'What is a favicon?','A browser tab icon for a website','A font weight','A print trim mark','A logo animation','A'),
(9,'Why is whitespace useful in layouts?','It wastes space','It improves readability and focus','It lowers contrast','It replaces typography','B'),
(9,'What is composition in design?','How elements are arranged visually','A file extension','A printer driver','A database index','A');

-- Content Writing Questions (test_id = 10)
INSERT INTO test_questions (test_id, question_text, option_a, option_b, option_c, option_d, correct_ans) VALUES
(10,'What does SEO stand for?','Search Engine Optimization','Social Engagement Online','Site Editing Options','Search Entry Output','A'),
(10,'What is a call-to-action?','A phone feature','Prompt encouraging user to take action','A design element','A social media post','B'),
(10,'What is a meta description?','An image caption','Brief summary of page for search engines','A heading tag','A URL slug','B'),
(10,'What does tone of voice mean in writing?','Font style','How a brand communicates personality through text','Writing speed','Document format','B'),
(10,'What is a keyword in content writing?','Any word in text','Word/phrase targeted for search ranking','A grammar term','A heading style','B'),
(10,'What is plagiarism?','Writing too fast','Copying others work without attribution','Using bullet points','Writing in passive voice','B'),
(10,'What is evergreen content?','Seasonal articles','Content that remains relevant over time','Video content','Short form posts','B'),
(10,'What is a blog post structure?','Introduction, body, conclusion','Title, image, footer','Header, sidebar, content','None of the above','A'),
(10,'What does engagement mean in content?','Server uptime','How users interact with content (likes, shares, comments)','Ad revenue','Page loading speed','B'),
(10,'What is proofreading?','Writing a first draft','Reviewing text for errors before publishing','Adding keywords','Formatting document','B'),
(10,'What is headline writing mainly meant to do?','Fill blank space','Grab attention and summarize the topic','Add database indexes','Replace the body text','B'),
(10,'What is audience targeting in content writing?','Writing for everyone the same way','Adapting content to a specific group of readers','Only using long words','Writing without structure','B'),
(10,'What is keyword stuffing?','Using relevant keywords naturally','Overusing keywords unnaturally in content','Writing metadata once','Creating headings only','B'),
(10,'What is readability?','How easy text is to understand','How long an article is','How many images are used','How fast a page loads','A'),
(10,'Why use subheadings in articles?','To make content harder to scan','To improve structure and readability','To hide keywords','To replace paragraphs','B'),
(10,'What is copywriting often focused on?','Persuading readers to take action','Only academic writing','Database design','Image retouching','A'),
(10,'What is proofreading checking for?','Grammar, spelling, punctuation, and clarity issues','Only image size','Server logs','Database triggers','A'),
(10,'What is editing in writing?','Improving content structure, flow, and clarity','Only exporting PDFs','Adding passwords','Compressing text','A'),
(10,'What is a tone guide?','A document that defines how writing should sound','A spell checker plugin','A video tutorial','A SQL view','A'),
(10,'Why is plagiarism harmful?','It improves SEO','It copies others work unethically and can damage credibility','It shortens articles','It creates better headlines','B'),
(10,'What is a primary keyword?','The main search term a page targets','A random repeated word','A database key','A print heading','A'),
(10,'What is internal linking?','Linking pages within the same website','Only using social media links','Linking to PDFs only','Removing navigation','A'),
(10,'Why should CTAs be clear?','So users know the next action to take','So the article gets longer','So search engines ignore them','So formatting looks dense','A'),
(10,'What is content brief?','A document outlining goals, audience, and requirements for a piece of content','A font style sheet','A security token','A CSS utility','A'),
(10,'What is search intent?','Why a user makes a search query','A database trigger','A page loading method','A graphic design mood','A'),
(10,'Which writing style is usually best for web content?','Clear and concise','Overly complex and academic always','Only poetic language','Sentence fragments only','A'),
(10,'What is an editorial calendar?','A schedule for planning and publishing content','A grammar checker','A cloud region','A payment gateway','A'),
(10,'Why is fact-checking important?','It improves credibility and accuracy','It increases font size','It removes headings','It creates backlinks automatically','A'),
(10,'What is brand voice consistency?','Changing tone on every page','Keeping communication style stable across content','Using only capital letters','Never updating content','B'),
(10,'What is skimmable content?','Content structured so readers can scan it quickly','Hidden content','A PDF only','Encrypted content','A');