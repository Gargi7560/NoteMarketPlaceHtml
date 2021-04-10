
<!DOCTYPE html>
<html lang="en">

<head><?php
    session_start();

    //Import database configuration
    require_once("../common/dbcontroller.php");
	$db_handle = new DBController();

    //Settings from Config file
    include '../common/configuration.php';

    $limit = 5;
    $sr_no = 1;

    $page = (isset($_GET['page']) && !empty($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;

    $start_from = ($page-1) * $limit; 

    $search_Str = (isset($_GET['search_member']) && !empty($_GET['search_member'])) ? $_GET['search_member'] : ""; 

    $orderBy = " ORDER BY ";
    
    $orderBy = (isset($_GET['orderBy']) && !empty($_GET['orderBy'])) ? $orderBy.$_GET['orderBy'] : $orderBy." US.CreatedDate DESC";

    $whereQuery = "";
    $paginationNoteUnderReviewQuery = "";

    $basedRegisteredMemberQuery = "SELECT US.UserID,US.FirstName,US.LastName,US.Email,US.CreatedDate From users US WHERE US.IsActive = 1 AND US.UserRoleID = ".$memberUserRoleID;
    
    if(!empty($search_Str)) {
        $whereQuery = " AND ( US.FirstName LIKE '%".$search_Str."%' OR US.LastName LIKE '%".$search_Str."%' OR US.Email LIKE '%".$search_Str."%' )"; 
    }

    $registeredMemberQuery = $basedRegisteredMemberQuery.$whereQuery.$orderBy." LIMIT ". $start_from. ",". $limit; 

    $registeredMemberResult = $db_handle->runQuery($registeredMemberQuery);

    $paginationRegisteredMemberQuery = $basedRegisteredMemberQuery.$whereQuery;  

    $paginationRegisteredMemberResult = $db_handle->numRows($paginationRegisteredMemberQuery);

    $total_records = $paginationRegisteredMemberResult;
    $total_pages = ceil($total_records / $limit);

    if($registeredMemberResult != "") {
        echo '<div class="data_table">
                <div class="table-responsive">
                <input type="hidden" id="hdnSortColumn" />
                    <table class="table fix_width_big_table text-center">
                        <thead>
                            <tr>
                                <th scope="col">SR NO.</th>
                                <th id="thFirstName" sortOrder="US.FirstName" class="allowSort" scope="col">FIRST NAME</th>
                                <th id="thLastName" sortOrder="US.LastName" class="allowSort" scope="col">LAST NAME</th>
                                <th id="thEmail" sortOrder="US.Email" class="allowSort" scope="col">EMAIL</th>
                                <th id="thJoiningDate" sortOrder="US.CreatedDate" class="allowSort table_big_head_fix_width" scope="col" >JOINING DATE</th>
                                <th scope="col">UNDER REVIEW NOTES</th>
                                <th scope="col">PUBLISHED NOTES</th>
                                <th scope="col">DOWNLOADED NOTES</th>
                                <th scope="col">TOTAL EXPENSES</th>
                                <th scope="col">TOTAL EARNINGS</th>
                            </tr>
                        </thead>
                        <tbody>';
                        foreach($registeredMemberResult as $value) {
                            
                            $memberNoteUnderReviewQuery = "SELECT COUNT(NoteDetailID) AS totalNoteUnderReview From notedetails WHERE SellerID = ".$value['UserID']." AND StatusID IN (".$submittedForReviewID.",".$inReviewID.") ";
            
                            $memberNoteUnderReviewResult = $db_handle->runQuery($memberNoteUnderReviewQuery);
                            
                            $memberPublishedQuery = "SELECT COUNT(NoteDetailID) AS totalPublished From notedetails WHERE SellerID = ".$value['UserID']." AND StatusID = ".$publishedID;
            
                            $memberPublishedResult = $db_handle->runQuery($memberPublishedQuery);
                            
                            $memberDownloadCountQuery = "SELECT COUNT(DownloadNoteID) AS totalDownload From downloadnotes WHERE SellerID = ".$value['UserID']." AND 	IsAttachmentDownloaded = 1";
            
                            $memberDownloadCountResult = $db_handle->runQuery($memberDownloadCountQuery);
                            
                            $memberTotalExpensesQuery = "SELECT SUM(PurchasedPrice) AS totalExpenses FROM downloadnotes WHERE IsSellerHasAllowedDownload = 1 AND DownloaderID = ".$value['UserID'];

                            $memberTotalExpensesResult = $db_handle->runQuery($memberTotalExpensesQuery);
                            
                            $earnedMoneyQuery = "SELECT SUM(PurchasedPrice) AS earnMoney FROM downloadnotes WHERE IsSellerHasAllowedDownload = 1 AND SellerID = ".$value['UserID'];

                            $earnedMoneyResult = $db_handle->runQuery($earnedMoneyQuery);
                            
                            echo "<tr>";
                            echo "<td>".$sr_no."</td>";
                            echo "<td>".$value['FirstName']."</td>";
                            echo "<td>".$value['LastName']."</td>";
                            echo "<td>".$value['Email']."</td>";
                            echo "<td>".date('d M Y, h:i',strtotime($value['CreatedDate']))."</td>";
                            
                            echo '<td><a href="'.$http_protocol.$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]).'/notes_under_review.php?user_id='.$value["UserID"].'">'.$memberNoteUnderReviewResult[0]['totalNoteUnderReview'].'</a></td>';
                            
                            echo '<td><a href="'.$http_protocol.$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]).'/published_note.php?user_id='.$value["UserID"].'">'.$memberPublishedResult[0]['totalPublished'].'</a></td>';
                            
                            echo '<td><a href="'.$http_protocol.$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]).'/downloaded_notes.php?user_id='.$value["UserID"].'">'.$memberDownloadCountResult[0]['totalDownload'].'</a></td>';
                            
                            if(!empty($memberTotalExpensesResult[0]['totalExpenses'])) {
                                echo '<td><a href=>$'.$memberTotalExpensesResult[0]['totalExpenses'].'</a></td>';
                            } else {
                                echo '<td><a href=>$0.00</a></td>';
                            }
                            
                            if(!empty($earnedMoneyResult[0]['earnMoney'])) {
                                echo '<td>$'.$earnedMoneyResult[0]['earnMoney'].'</td>';
                            } else {
                                echo '<td>$0.00</td>';
                            }
                            
                            echo '<td class="dropdown">
                                <a href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                    <img src="images/Dashboard/dots.png" alt="details" class="img-responsive">
                                </a>
                                <div class="dropdown-menu">
                                    <a class="dropdown-item" href="'.$http_protocol.$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]).'/member_details.php?user_id='.$value["UserID"].'">View More Details</a>
                                    
                                    <a class="dropdown-item" href="#" onclick="deactivateMember('.$value["UserID"].');">Deactive</a>
                                </div>
                            </td>
                        </tr>';
                            $sr_no++;
                        }
                        echo '</tbody>
                            </table>
                        </div>
                    </div>';
        //Pagination Start
        echo '<ul id="paging" class="pagination-filters">';
        echo '<li class="pagination"><a onclick="searchMemberOnPortal('.($page-1).')" class="button">❮</a></li>';

        for ($i=1; $i<=$total_pages; $i++) { echo '<li class="pagination"><a ' ; 
            if($i==$page) { 
                echo 'class = "active"' ; 
            } echo 'onclick="searchMemberOnPortal('.$i.')" >' .$i.'</a>
            </li>';
        }
        echo '<li class="pagination"><a onclick="searchMemberOnPortal('.($page+1).')" class="button">❯</a></li>';
        echo '</ul>';
        //Pagination End
    } else {
        echo '<div class="data_table">No Records Found!!</div>';
    }
?>
<script type="text/javascript">
    $(document).ready(function() {
        $("th.allowSort").click(function() {
            var isAsc = true;
            if($(this).hasClass('ascending')) {
                isAsc = false;
            }
            
            $('#hdnMemberPageSortColumn').val($(this).attr('id'));
            if(isAsc) {
                $('#hdnMemberPageSortDir').val('ASC');
                $('#hdnMemberPageSortOrder').val($(this).attr('sortOrder') + " ASC ");
            } else {
                $('#hdnMemberPageSortDir').val('DESC');
                $('#hdnMemberPageSortOrder').val($(this).attr('sortOrder') + " DESC ");                
            }
            searchMemberOnPortal(1);
        });
    });
     
</script>

    <!--important meta tags-->
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <!--Title-->
    <title>Notes MarketPlace</title>
    
    <!--Favicon-->
    <link rel="shortcut icon" href="images/home/favicon.ico">
    
    <!--JQuery-->
    <script src="js/jquery.min.js"></script>

    <!--Popper JS-->
    <script src="js/popper/popper.min.js"></script>
    
    <!--Bootstrap JS-->
    <script src="js/bootstrap/bootstrap.min.js"></script>

    <!-- Custom JS -->
    <script src="js/script_admin.js"></script>

    <!--Google Fonts-->
    <link href="https://fonts.googleapis.com/css2?family=Open+Sans:wght@300;400;600;700&display=swap" rel="stylesheet">

    <!--Bootstrap CSS -->
    <link rel="stylesheet" href="css/bootstrap/bootstrap.min.css">

    <!--Custom CSS-->
    <link rel="stylesheet" href="css/style_admin.css">

    <!--Responsive CSS-->
    <link rel="stylesheet" href="css/responsive_admin.css">
    
</head>

<body data-spy="scroll" class="overflow-auto sticky-header">
    <div class="wrapper">
        
        <!--Header Start-->
        <header class="site-header">
        <div class="container">
            <div class="header-wrapper">
                <div class="logo-wrapper">
                    <a class="navbar-brand" href="#"><img src="images/logo_pur/top-logo.png" alt="logo"></a>
                                      
                    <!--Mobile Menu Open Button-->
                    <span id="mobile-nav-open-btn">&#9776;</span>
                    
                </div>
                <div class="navigation-wrapper">
                    <nav class="main-nav navbar navbar-expand-md">
                        <div class="collapse navbar-collapse">

                            <ul class="menu-navigation">
                                <li><a href="admin_dashboard-1.html" class="val_content">Dashboard</a></li>
                                <li class="dropdown">
                                    <a href="#" role="button" class="val_content" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Notes
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="notes_under_review.html">Notes Under Review</a>
                                        <a class="dropdown-item" href="published_note.html">Published Notes</a>
                                        <a class="dropdown-item" href="downloaded_notes.html">Downloaded Notes</a>
                                        <a class="dropdown-item" href="rejected_notes.html">Rejected Notes</a>

                                    </div>
                                </li>
                                <li><a href="members_page.html" class="val_content">Members</a></li>
                                <li class="dropdown">
                                    <a href="#" role="button" class="val_content" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Reports
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="spam_reports.html">Spam Reports</a>
                                    </div>
                                </li>
                                <li><a href="#" class="val_content">Settings</a></li>
                                <li class="dropdown">
                                    <a href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <img src="images/Add-notes/user-img.png" alt="user" class="img-responsive">
                                    </a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#">My Profile</a>
                                        <a class="dropdown-item" href="#">Change Password</a>
                                        <a class="dropdown-item pur_col" href="#">LOGOUT</a>
                                    </div>
                                </li>
                                <li><a href="#">
                                    <button type="button" class="btn btn-outline-primary btn-purple">Logout</button> </a></li>
                            </ul>
                        </div>
                    </nav>
                </div>
                
                
                    <!--Mobile Menu-->
                    <div id="mobile-nav">
                        <!--Mobile Menu Close Button-->
                        <span id="mobile-nav-close-btn">&times;</span>
                        <div id="mobile-nav-content">
                            <ul class="menu-navigation">
                                <li><a href="downloaded_notes.html" class="val_content">Dashboard</a></li>
                                <li class="dropdown">
                                    <a href="#" role="button" class="val_content" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Notes
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="notes_under_review.html">Notes Under Review</a>
                                        <a class="dropdown-item" href="published_note.html">Published Notes</a>
                                        <a class="dropdown-item" href="downloaded_notes.html">Downloaded Notes</a>
                                        <a class="dropdown-item" href="rejected_notes.html">Rejected Notes</a>

                                    </div>
                                </li>
                                <li><a href="members_page.html" class="val_content">Members</a></li>
                                <li class="dropdown">
                                    <a href="#" role="button" class="val_content" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        Reports
                                    </a>
                                    <div class="dropdown-menu" aria-labelledby="navbarDropdown">
                                        <a class="dropdown-item" href="spam_reports.html">Spam Reports</a>
                                    </div>
                                </li>
                                <li><a href="#" class="val_content">Settings</a></li>
                                <li class="dropdown">
                                    <a href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                                        <img src="images/Add-notes/user-img.png" alt="user" class="img-responsive">
                                    </a>
                                    <div class="dropdown-menu">
                                        <a class="dropdown-item" href="#">My Profile</a>
                                        <a class="dropdown-item" href="#">Change Password</a>
                                        <a class="dropdown-item pur_col" href="#">LOGOUT</a>
                                    </div>
                                </li>
                                <li><a href="#">
                                    <button type="button" class="btn btn-outline-primary btn-purple">Logout</button> </a></li>
                            </ul>

                        </div>
                    </div>
                
            </div>
        </div>
    </header>
        <!--Header End-->

        <!--Search Notes Start-->
        <section id="admin_notes_details" class="pad_100_for_pages">
<div class="container">
                <div class="content-box-xs">
                    <p class="small-heading left_heading-1">Notes Details</p>
                   
                    <div class="row">
                        <div class="col-lg-7 row_fix_bottom">

                            <div class="row">
                                <div class="col-md-5">
                                    <div class="note_img">
                                        <img src="images/Notes_Details/1.jpg" alt="note">
                                    </div>
                                </div>
                                <br>
                                <div class="col-md-7">
                                    <span class="common-heading-1 left_heading-1">Computer Science</span>
                                    <p class="middle-heading left_heading-1">Sciences</p>
                                    <p class="val_content">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Magni officia animi enim fugiat vero voluptatum voluptate excepturi possimus quasi hic quaerat quia veniam, facilis suscipit!</p>
                                    
                                    <!--Thank You  Button tigger-->
                                    <div class="small-btn general-btn">
                                        <button type="button" class="btn btn-outline-primary btn-purple" data-toggle="modal" data-target="#myModal">DOWNLOAD/$15</button>
                                        
                                    </div>
                                    
                                    <!--Thank You Popup-->
                                    <div class="modal fade" id="myModal" tabindex="-1" aria-labelledby="myModalLabel" aria-hidden="true">
                                        <div class="modal-dialog">
                                            <div class="modal-content">

                                                <div class="modal-body">
                                                    <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                                        <span aria-hidden="true"><img src="images/Notes_Details/close.png" alt="close" class="close-btn"></span>
                                                    </button>
                                                    <div class="thanks_popup">


                                                        <div class="success_icon text-center">
                                                            <img src="images/Notes_Details/SUCCESS.png" alt="success">
                                                        </div>
                                                        <h4 class="common-heading-1 center_heading-1">Thank you for purchasing</h4>
                                                        <p class="middle-heading"> Dear Smith,
                                                        </p>
                                                        <p class="val_content">As this is paid notes - you need to pay to seller Gargi Patel offline. We will send him an email that you want to download this note. He may contact you furthur for payment process completion. </p>
                                                        <p class="val_content">In case, you have urgency,<br>
                                                            please contact us on +919543210987.
                                                        </p>
                                                        <p class="val_content">Once he receives the payment and acknowledge us - selected notes you can see over my downloads tab for download.
                                                        </p>
                                                        <p class="val_content">Have a good day.</p>
                                                    </div>

                                                </div>

                                            </div>
                                        </div>
                                    </div>
                                </div>

                            </div>
                        </div>
                        <div class="col-lg-5">
                            <ul class="notes_Details_ul list-unstyled">
                                <li> Institution: <span> University of California</span></li>
                                <li> Country: <span> United States</span></li>
                                <li> Course Name: <span> Computer Engineering</span></li>
                                <li> Course Code: <span> 248705</span></li>
                                <li> Professor: <span> Mr.Richard Brown</span></li>
                                <li> Number of Pages: <span> 277</span></li>
                                <li> Approved Date: <span> November 25 2020</span></li>
                                <li> Rating:
                                          <span> <div class="rate">
                                            <input type="radio" id="star5" name="rate" value="5" />
                                            <label for="star5" title="text">5 stars</label>
                                            <input type="radio" id="star4" name="rate" value="4" />
                                            <label for="star4" title="text">4 stars</label>
                                            <input type="radio" id="star3" name="rate" value="3" />
                                            <label for="star3" title="text">3 stars</label>
                                            <input type="radio" id="star2" name="rate" value="2" />
                                            <label for="star2" title="text">2 stars</label>
                                            <input type="radio" id="star1" name="rate" value="1" />
                                            <label for="star1" title="text">1 star</label>
                                     </div> 100 Reviews</span></li>
                                <li class="val_content red_text"> 5 Users marked this note as inappropriate</li>
                            </ul>
                        </div>
                    </div>
                </div>
                <hr>
                <div class="content-box-xs">
                    <div class="row">
                        <div class="col-lg-5 row_fix_bottom">
                            <p class="small-heading left_heading-1">Notes Preview</p>
                            
                            <div class="note_preview">
                                <!-- responsive iframe -->
                                <!-- ============== -->

                                <div class="set-border-note-pdf">
                                    <div class="responsive-wrapper responsive-wrapper-padding-bottom-90pct">
                                        <iframe src="http://unec.edu.az/application/uploads/2014/12/pdf-sample.pdf">
                                            <p style="font-size: 110%;"><em><strong>ERROR: </strong>
                                                    An &#105;frame should be displayed here but your browser version does not support &#105;frames.</em> Please update your browser to its most recent version and try again, or access the file <a href="http://unec.edu.az/application/uploads/2014/12/pdf-sample.pdf">with this link.</a></p>
                                        </iframe>
                                    </div>
                                </div>

                            </div>
                        </div>

                        <div class="col-lg-7">
                            <p class="small-heading left_heading-1">Customer Reviews</p>

                            <div class="cust_review">
                                <div class="row">
                                    <div class="col-md-2">
                                        <span><img src="images/clients/team-2.jpg" alt="customer" class="img-responsive rounded-circle cust_img"></span>
                                    </div>

                                    <div class="col-md-10">
                                        <span class="val_content"><b>Richard Brown</b></span>
                                        <br>
                                        <div class="rate">
                                            <input type="radio" id="star5" name="rate" value="5" />
                                            <label for="star5" title="text">5 stars</label>
                                            <input type="radio" id="star4" name="rate" value="4" />
                                            <label for="star4" title="text">4 stars</label>
                                            <input type="radio" id="star3" name="rate" value="3" />
                                            <label for="star3" title="text">3 stars</label>
                                            <input type="radio" id="star2" name="rate" value="2" />
                                            <label for="star2" title="text">2 stars</label>
                                            <input type="radio" id="star1" name="rate" value="1" />
                                            <label for="star1" title="text">1 star</label>
                                        </div>
                                        <br>
                                        <p class="val_content">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Assumenda fugiat praesentium tenetur reiciendis!</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-2">
                                        <span><img src="images/clients/team-2.jpg" alt="customer" class="img-responsive rounded-circle cust_img"></span>
                                    </div>

                                    <div class="col-md-10">
                                        <span class="val_content"><b>Richard Brown</b></span>
                                        <br>
                                        <div class="rate">
                                            <input type="radio" id="star5" name="rate" value="5" />
                                            <label for="star5" title="text">5 stars</label>
                                            <input type="radio" id="star4" name="rate" value="4" />
                                            <label for="star4" title="text">4 stars</label>
                                            <input type="radio" id="star3" name="rate" value="3" />
                                            <label for="star3" title="text">3 stars</label>
                                            <input type="radio" id="star2" name="rate" value="2" />
                                            <label for="star2" title="text">2 stars</label>
                                            <input type="radio" id="star1" name="rate" value="1" />
                                            <label for="star1" title="text">1 star</label>
                                        </div>
                                        <br>
                                        <p class="val_content">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Assumenda fugiat praesentium tenetur reiciendis!</p>
                                    </div>
                                </div>
                                <hr>
                                <div class="row">
                                    <div class="col-md-2">
                                        <span><img src="images/clients/team-2.jpg" alt="customer" class="img-responsive rounded-circle cust_img"></span>
                                    </div>

                                    <div class="col-md-10">
                                        <span class="val_content"><b>Richard Brown</b></span>
                                        <br>
                                        <div class="rate">
                                            <input type="radio" id="star5" name="rate" value="5" />
                                            <label for="star5" title="text">5 stars</label>
                                            <input type="radio" id="star4" name="rate" value="4" />
                                            <label for="star4" title="text">4 stars</label>
                                            <input type="radio" id="star3" name="rate" value="3" />
                                            <label for="star3" title="text">3 stars</label>
                                            <input type="radio" id="star2" name="rate" value="2" />
                                            <label for="star2" title="text">2 stars</label>
                                            <input type="radio" id="star1" name="rate" value="1" />
                                            <label for="star1" title="text">1 star</label>
                                        </div>
                                        <br>
                                        <p class="val_content">Lorem ipsum dolor sit amet, consectetur adipisicing elit. Assumenda fugiat praesentium tenetur reiciendis!</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </section>
        <!--Search Notes End-->


    <!--Footer Start-->
    <div class="admin-footer">
        <hr>
        <div class="container">
        <div class="row">
            <div class="col-md-6">
                <span class="admin-footer-content">version:1.1.24</span>
            </div>
            <div class="col-md-6">
                <span class="admin-footer-content pull-right">Copyright &#169; TatvaSoft All rights reserved.</span>
            </div>
        </div>
        </div>
    </div>
    <!--Footer End-->

    </div>

</body>

</html>