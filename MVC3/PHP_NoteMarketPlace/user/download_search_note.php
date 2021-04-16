<?php

    //Settings from Config file
    include '../common/configuration.php';

    //Session start
    include 'manage_user_session.php';

    //Import database configuration
    require_once("../common/dbcontroller.php");
	$db_handle = new DBController();
    
    $limitDownload = 10;
    $sr_no = 1;
    $page = (isset($_GET['page']) && !empty($_GET['page']) && $_GET['page'] > 0) ? $_GET['page'] : 1;

    $start_from = ($page-1) * $limitDownload; 

    $search_Str = (isset($_GET['search_download_note']) && !empty($_GET['search_download_note'])) ? $_GET['search_download_note'] : ""; 

    $orderBy = " ORDER BY ";
    
    $orderBy = (isset($_GET['orderBy']) && !empty($_GET['orderBy'])) ? $orderBy.$_GET['orderBy'] : $orderBy." DN.AttachmentDownloadedDate DESC";

    $whereQuery = "";
    $paginationBuyerQuery = "";

    $basedDownloadQuery = "SELECT DN.DownloadNoteID,ND.NoteDetailID,DN.NoteTitle,NC.CategoryName,US.Email,DN.PurchasedPrice,RD.Value,DN.AttachmentDownloadedDate,UP.PhoneNumber_CountryID,UP.PhoneNumber,CN.PhoneCode,US.FirstName FROM downloadnotes DN 
    INNER JOIN notedetails ND ON ND.NoteDetailID = DN.NoteDetailID 
    INNER JOIN notecategories NC ON DN.NoteCategory = NC.NoteCategoryID 
    INNER JOIN users US ON DN.DownloaderID = US.UserID 
    INNER JOIN userprofiledetails UP ON DN.DownloaderID = UP.UserID 
    INNER JOIN countries CN ON CN.CountryID = UP.PhoneNumber_CountryID 
    INNER JOIN referencedata RD ON ND.SellingModeID = RD.DataValue AND RD.ReferenceCategory = 'SellingMode' 
    WHERE IsSellerHasAllowedDownload = 1 AND DN.DownloaderID = ".$_SESSION['user_id'];

    if(!empty($search_Str)) {
        $whereQuery = " AND ( DN.NoteTitle LIKE '%".$search_Str."%' OR NC.CategoryName LIKE '%".$search_Str."%' )"; 
    }
    
    $downloadQuery = $basedDownloadQuery.$whereQuery.$orderBy." LIMIT ". $start_from. ",". $limitDownload; 
    $downloadResult = $db_handle->runQuery($downloadQuery);

    $paginationDownloadQuery = $basedDownloadQuery.$whereQuery;   
    $paginationDownloadResult = $db_handle->numRows($paginationDownloadQuery);

    $total_records = $paginationDownloadResult;
    $total_pages = ceil($total_records / $limitDownload);
          
    if($downloadResult != ""){
        echo '<div class="data_table">
            <div class="table-responsive">
                <table class="table fix_width_table second_col_pur">
                    <thead>
                        <tr>
                            <th scope="col">SR NO.</th>
                            <th id="thNoteTitle" sortOrder="DN.NoteTitle" class="allowSort" scope="col">NOTE TITLE</th>
                            <th id="thCategory" sortOrder="NC.CategoryName" class="allowSort" scope="col">CATEGORY</th>
                            <th id="thBuyer" sortOrder="US.Email" class="allowSort" scope="col">BUYER</th>
                            <th id="thSellType" sortOrder="RD.Value" class="allowSort" scope="col">SELL TYPE</th>
                            <th id="thPrice" sortOrder="DN.PurchasedPrice" class="allowSort" scope="col">PRICE</th>
                            <th id="thDownloadDt" sortOrder="DN.AttachmentDownloadedDate" class="allowSort" scope="col">DOWNLOADED DATE/TIME</th>
                            <th scope="col"></th>
                            <th scope="col"></th>
                        </tr>
                    </thead>
                    <tbody>';
                    foreach($downloadResult as $value) {
                        echo "<tr>";
                        echo "<td>".$sr_no."</td>";
                        echo '<td><a href=';
                        echo $http_protocol.$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]).'/note_details.php?note_id='.$value["NoteDetailID"];
                        echo '>'.$value["NoteTitle"].'</a></td>';
                        echo "<td>".$value['CategoryName']."</td>";
                        echo "<td>".$value['Email']."</td>";
                        echo "<td>".$value['Value']."</td>";
                        echo "<td>".$value['PurchasedPrice']."</td>";
                        if(!empty($value['AttachmentDownloadedDate'])){
                        echo "<td>".date('d M Y h:i:s',strtotime($value['AttachmentDownloadedDate']))."</td>";
                        }else{
                            echo "<td></td>";
                        }
                        echo '<td><a href='; 
                        echo $http_protocol.$_SERVER["HTTP_HOST"].dirname($_SERVER["PHP_SELF"]).'/note_details.php?note_id='.$value["NoteDetailID"]; 
                        echo '><img src="images/Dashboard/eye.png" alt="view" class="icon_space"></a></td>';
                        
                        echo '<td class="dropdown"><a href="#" role="button" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><img src="images/Dashboard/dots.png" alt="confirmation" class="icon_space"></a>
                        <div class="dropdown-menu"><a class="dropdown-item" href="#" onclick="downloadNoteFromTable(';
                        echo $value["DownloadNoteID"];
                        echo ')">Download Notes</a><a class="dropdown-item" href="#" onclick="noteReview(';
                        echo $value["NoteDetailID"];
                        echo ');">Add Reviews/Feedback</a><a class="dropdown-item" href="#" onclick="inappropriateNote(';
                        echo $value["NoteDetailID"].",'".$value["NoteTitle"];
                        echo '\')">Repoart as Inappropriate</a></div></td>';
                        echo "</tr>";
                        $sr_no++;
                    }          
                    echo '</tbody>
                    </table>
                </div>
            </div>';
        
    //Pagination Start
    echo '<ul id="paging" class="pagination-filters">';
    echo '<li class="pagination"><a onclick="searchNoteDownload('.($page-1).')" class="button">❮</a></li>';

    for ($i=1; $i<=$total_pages; $i++) { echo '<li class="pagination"><a ' ; 
        if($i==$page) { 
            echo 'class = "active"' ; 
        } echo 'onclick="searchNoteDownload('.$i.')" >' .$i.'</a>
        </li>';
    }
    echo '<li class="pagination"><a onclick="searchNoteDownload('.($page+1).')" class="button">❯</a></li>';
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
            
            $('#hdnDownloadSortColumn').val($(this).attr('id'));
            if(isAsc) {
                $('#hdnDownloadSortDir').val('ASC');
                $('#hdnDownloadSortOrder').val($(this).attr('sortOrder') + " ASC ");
            } else {
                $('#hdnDownloadSortDir').val('DESC');
                $('#hdnDownloadSortOrder').val($(this).attr('sortOrder') + " DESC ");                
            }
            searchNoteDownload(1);
        });
    });
</script>
                