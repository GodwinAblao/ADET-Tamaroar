GENERAL SCOPE OF THE PROJECT

## 1 .CREATE A WEB APPLICATION/ SYSTEM USING PHP : LIBRARY SYSTEM

## 2. THE APPLICATION MUST HAVE THE FOLLOWING CRUD FUNCTIONALITY:

    -CREATE
    -READ
    -UPDATE
    -DELETE FUNCTIONS : FOR ALL FEATURES AND CORE PROCESESSES

## 3. MAKE SURE THAT YOU HAVE A DATABASE FOR BACK UP/STORAGE OF INFORMATION

## 4. THE APPLICATION MUST BE HOSTED ONLINE

## 5. CORRECT VALIDATIONS MUST BE APPLIED

### REQUIRMENTS ###

    1. BOOK ID - the Book ID will be 'GENERATED' from the Book Details

        ex. THFEB102022-FIC00001

        TH - First 2 letters from the Book Title
        FEB – month (published)
        10 - day (added to the system)
        2022 - year (published)
        FIC - category of book ( FIC = Fiction)
        00001 - count of books on the library

    2. Only 2 BOOKS will be allowed to be borrowed per student ( 7 days ,included week ends)
        -A student can only borrow up to 2 books at a time.
        -The borrowing period is strictly 7 days, including weekends.
        -The student must return the borrowed books on or before the 7th day.
        -If the student fails to return on or before the due date, the system will calculate overdue days and apply a fine.

    3. Fine of ₱ 10.00 per day per book
        -If the book is returned late, the system will:
            -Calculate the number of days overdue
            -Multiply that by ₱10 × number of days × number of books

    4. Status of Book - you cannot borrow an Archived Book and cannot delete any book

    5. Minimum of 50 books

    6. Admin Account (Librarian), User Account (Student)

