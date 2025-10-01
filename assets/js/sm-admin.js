/* --- Student Picture Upload Box --- */
#sm_student_picture_box {
    width: 100px;
    height: 100px;
    border: 2px dashed #ccc;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
    position: absolute;
    right: 0;
    top: 0;
    background: #fafafa;
    border-radius: 4px;
    transition: border-color 0.3s;
}

#sm_student_picture_box:hover {
    border-color: #0073aa;
}

#sm_student_picture_box img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 2px;
}

#sm_student_picture_box span {
    color: #aaa;
    font-size: 12px;
    text-align: center;
    padding: 5px;
}

/* --- Level Badge --- */
.sm-level-badge {
    display: inline-block;
    padding: 2px 8px;
    border-radius: 12px;
    font-size: 11px;
    font-weight: 500;
    background: #0073aa;
    color: white;
}

/* --- Empty State --- */
.sm-empty-state h3 {
    color: #666;
    margin-bottom: 8px;
}

.sm-empty-state p {
    color: #999;
    margin-bottom: 20px;
}

/* --- Form Header --- */
.sm-form-header {
    border-bottom: 1px solid #ddd;
    padding-bottom: 15px;
}

/* --- Action Buttons in Table --- */
.wp-list-table .button-small {
    margin-right: 5px;
}

.wp-list-table .button-small .dashicons {
    font-size: 14px;
    line-height: 26px;
}

/* --- Header Actions --- */
.sm-header-actions {
    background: white;
    padding: 15px 20px;
    border: 1px solid #ddd;
    border-radius: 4px;
    margin-bottom: 10px;
}

/* --- Responsive adjustments --- */
@media (max-width: 782px) {
    #sm_student_picture_box {
        position: relative;
        margin-bottom: 20px;
    }
    
    .sm-header-actions {
        flex-direction: column;
        gap: 10px;
    }
}