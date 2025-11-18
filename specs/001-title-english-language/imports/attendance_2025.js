/**
 * @OnlyCurrentDoc
 * @AuthScope https://www.googleapis.com/auth/spreadsheets
 * @AuthScope https://www.googleapis.com/auth/drive
 * @AuthScope https://www.googleapis.com/auth/gmail.compose
 * @AuthScope https://www.googleapis.com/auth/documents
 */

// --- CONFIGURATION ---
// The ID of the 'Courses_All' workbook where your master list is stored.
const COURSES_WORKBOOK_ID = '1ZDaGAzYVjmGYEx-sGgQinUZbl_lv1tCOifFhXIpKiYE';
// The name of the sheet you are editing.
const CONFIG_SHEET_NAME = 'Config';
// The column number you edit to trigger the lookup (A=1, B=2, etc.).
const LOOKUP_COLUMN = 1; 
// The name of your main sheet where you register students.
const SOURCE_SHEET_NAME = 'Registered_Students';
// The column number where the 'ClassName' is located (A=1, B=2, etc.).
const CLASS_NAME_COLUMN = 2; 
const TRELLO_CARD_ID_COLUMN = 3;
const TRELLO_WAIT_ID = '6861aab0112165ce4fa4bcb6';
const TRELLO_IN_AS_ID = '686e22e5894437d0f335c918';
const TRELLO_CERT_ID ='639097e15b4d67031734da3d';

/**
 * Simple function to trigger Gmail permissions review
 * Run this function to force Apps Script to request authorization again
 */

function triggerPermissionsReview() {
  try {
    // This will trigger the authorization dialog for Gmail
    var aliases = GmailApp.getAliases();
    
    Logger.log("Permissions check complete!");
    Logger.log("Your email aliases: " + aliases.join(", "));
    
    // Optional: Show a success message if running from Sheets
    try {
      SpreadsheetApp.getUi().alert("Permissions review completed successfully!");
    } catch (e) {
      // Not running from Sheets, that's okay
      Logger.log("Success message skipped (not in Sheets context)");
    }
    
  } catch (e) {
    Logger.log("Error: " + e.toString());
    throw e;
  }
}
// ---------------------

/**
 * Creates a custom menu in the Google Sheet UI when the spreadsheet is opened.
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();

  ui.createMenu('Attendance System Tools')
    
    // Submenu for initial setup and configuration tasks
    .addSubMenu(ui.createMenu('Configuration - CONFIG')
      .addItem('Fetch Course Details for Selected Row', 'runCourseLookupOnSelectedRow')
      .addItem('Create New Class Sheet from Selected Row', 'createClassSheetFromSelectedRow')
      .addItem('Create New Attendance URLs', 'createAttendanceURLs')
      .addItem('Create Attendance URL for selected row', 'createURLForSelectedRow')
      .addItem('Update Index Sheet', 'createSheetIndex'))
      
    .addSeparator()
    .addSubMenu(ui.createMenu('Teacher Schedules - CONFIG')
    .addItem('Update Teacher Schedule', 'generateFullSchedule'))
    .addSeparator()

    // Submenu for day-to-day class management
    .addSubMenu(ui.createMenu('Class Management')
      .addItem('Move Registered Student to Class Sheet', 'moveStudentToClassSheet')
      .addItem('Move All Students to Their Classes', 'moveAllStudentsToClassSheets'))

    .addSeparator()

    // Submenu for the multi-step continuation email process
    .addSubMenu(ui.createMenu('Continuation Process')
    .addItem('1. Copy Attendance Sheet to Continuation Sheet', 'copyDataToContinuationEmails') // Renamed for clarity
      .addItem('2. Update Details from Dropdown', 'updateCourseDetails') // Including the manual update function we created
      .addItem('3. Update Course Details once suggested course is selected', 'updateCourseDetails') 
      .addItem('4. Send Email Drafts', 'sendContinuationEmails'))

    .addSeparator()

    // Submenu for end-of-course tasks like certificates
    .addSubMenu(ui.createMenu('Certificates')
    .addItem('1. Copy Students from Attendance Sheet to Certificates Sheet', 'copyDataToCertificates')
      .addItem('2. Generate Certificates & Draft Emails', 'generateAndDraftEmails_v3'))
      
    .addToUi();
}

// Global constant for available levels
const LEVELS = ['A1-','A1','A1+','A2-','A2','A2+','A2+ Pre-Int-', 'A2+ Pre-Int','A2+ Pre-Int+','B1-','B1','B1+','B2-','B2','B2+','C1-','C1', 'C1+','C2'];

// --- ONE-TIME SETUP FUNCTION (Leave it here or delete it after running once) ---
function setup() {
  const SPREADSHEET_ID = "1Yc-Alkaux7GTd7Mwaqpkn7E_wL3Luhua01Kaus1zaco"; 
  PropertiesService.getScriptProperties().setProperty('SPREADSHEET_ID', SPREADSHEET_ID);
  //Logger.log('Spreadsheet ID has been saved successfully!');
}

// --- Helper to get the Spreadsheet object reliably ---
function getSpreadsheet() {
  const SPREADSHEET_ID = PropertiesService.getScriptProperties().getProperty('SPREADSHEET_ID');
  if (!SPREADSHEET_ID) {
    throw new Error("Spreadsheet ID has not been set. Please run the 'setup' function once.");
  }
  return SpreadsheetApp.openById(SPREADSHEET_ID);
}

function doGet(e) {
  try {
    const activeUser = Session.getActiveUser();
    const userEmail = activeUser ? activeUser.getEmail() : null;
    let isAdmin = false;
    if (userEmail) {
      const adminList = getAdminList();
      isAdmin = adminList.includes(userEmail);
    }
    let template;
    if (e.parameter.page === 'admin') {
      if (isAdmin) {
        template = HtmlService.createTemplateFromFile('Admin');
      } else {
        return HtmlService.createHtmlOutput('<h1>Access Denied</h1><p>You do not have permission to view this page.</p>');
      }
    } else {
      template = HtmlService.createTemplateFromFile('Index');
      template.initialClass = e.parameter.class || null;
    }
    return template.evaluate().setTitle('Class Attendance System').addMetaTag('viewport', 'width=device-width, initial-scale=1');
  } catch (err) {
    Logger.log(err);
    return HtmlService.createHtmlOutput('<h1>An error occurred</h1><p>Could not load the application. Details: ' + err.message + '</p>');
  }
}

function getAdminList() {
  const ss = getSpreadsheet(); // MODIFIED
  const sheet = ss.getSheetByName('Admins');
  if (!sheet) return [];
  return sheet.getRange(2, 1, sheet.getLastRow() - 1, 1).getValues().flat();
}

function getClasses() {
  const ss = getSpreadsheet(); // MODIFIED
  const configSheet = ss.getSheetByName('Config');
  if (!configSheet) return [];
  const configData = configSheet.getRange(2, 1, configSheet.getLastRow() - 1, 2).getValues();
  return configData.filter(row => row[0] && row[1]).map(row => ({ className: row[0], sheetName: row[1] }));
}

function getClassDataGrid(sheetName) {
  try {
    const ss = getSpreadsheet();
    const configSheet = ss.getSheetByName('Config');
    
    // Read headers to find column indices dynamically
    const configHeaders = configSheet.getRange(1, 1, 1, configSheet.getLastColumn()).getValues()[0];
    const teacherNameIndex = configHeaders.indexOf('TeacherName');
    const secondTeacherNameIndex = configHeaders.indexOf('SecondTeacherName');

    const configData = configSheet.getDataRange().getValues();
    configData.shift(); // Remove header row
    const classConfig = configData.find(row => row[1] === sheetName);
    if (!classConfig) { return { error: 'Class configuration not found.' }; }
    
    // Get teacher names using their indices. Default to null if not found.
    const teacher1 = (teacherNameIndex > -1) ? classConfig[teacherNameIndex] : null; // <-- NEW
    const teacher2 = (secondTeacherNameIndex > -1) ? classConfig[secondTeacherNameIndex] : null; // <-- NEW

  const [className, , startDate, endDate, daysOfWeekStr] = classConfig;


  // --- LOGGING & FIX STARTS HERE ---

    //Logger.log('--- Date Debugging for Class: ' + className + ' ---');
    //Logger.log('Raw start date from sheet: ' + startDate);
   // Logger.log('Raw end date from sheet: ' + endDate);

    let currentDate = new Date(startDate);
    let finalDate = new Date(endDate);

   //Logger.log('Initial JS Object (start): ' + currentDate.toString());
    //Logger.log('Initial JS Object (end):   ' + finalDate.toString());

    // ROBUST FIX: Set time to noon before normalizing to avoid timezone truncation errors.

    currentDate.setHours(12, 0, 0, 0); 
    finalDate.setHours(12, 0, 0, 0);

    // Normalize both dates to midnight UTC.
    currentDate.setUTCHours(0,0,0,0);
    finalDate.setUTCHours(0,0,0,0);

    //Logger.log('Normalized UTC Start Date: ' + currentDate.toUTCString());
   // Logger.log('Normalized UTC End Date:   ' + finalDate.toUTCString());
    
    const scheduledDays = daysOfWeekStr.split(',');
    const schedule = [];

   while (currentDate <= finalDate) {
      const dayInitial = ['Su', 'M', 'T', 'W', 'Th', 'F', 'S'][currentDate.getUTCDay()]; // Use getUTCDay() for consistency
      if (scheduledDays.includes(dayInitial)) {
        const yyyy = currentDate.getUTCFullYear();
        const mm = String(currentDate.getUTCMonth() + 1).padStart(2, '0');
        const dd = String(currentDate.getUTCDate()).padStart(2, '0');
        schedule.push(`${yyyy}-${mm}-${dd}`);
      }
      // --- FIXED LINE ---
      // Use setUTCDate to increment the day, avoiding local timezone interference.
      currentDate.setUTCDate(currentDate.getUTCDate() + 1);
    }

     // --- NEW: Calculate the mid-point date ---
    const midPointIndex = schedule.length > 0 ? Math.floor(schedule.length / 2) : -1;
    const midPointDate = midPointIndex > -1 ? schedule[midPointIndex] : null;

    // --- LOGGING & FIX ENDS HERE ---

  const classSheet = ss.getSheetByName(sheetName);
  const studentData = classSheet.getDataRange().getValues();
  const headers = studentData.shift();
  const nameIndex = headers.indexOf('Student Name'), emailIndex = headers.indexOf('Student Email'), countryIndex = headers.indexOf('Country'), datesPresentIndex = headers.indexOf('Dates Present'), initialLevelIndex = headers.indexOf('Initial Level'), endLevelIndex = headers.indexOf('Mid Level'), notesIndex = headers.indexOf('Teacher Notes'), prevCoursesIndex = headers.indexOf('Previous Courses');
  const students = studentData.map(row => {
    const presentDatesStr = row[datesPresentIndex] || '', presentDatesSet = new Set(presentDatesStr.toString().split(',').map(d => d.trim()).filter(Boolean)), attendanceMap = {};
    schedule.forEach(date => { attendanceMap[date] = presentDatesSet.has(date); });
    let attendancePercentage = 0;
    if (schedule.length > 0) { const presentCount = Object.values(attendanceMap).filter(present => present).length; attendancePercentage = (presentCount / schedule.length) * 100; }
    return { name: row[nameIndex], country: row[countryIndex], email: row[emailIndex], initialLevel: row[initialLevelIndex], endLevel: row[endLevelIndex], notes: row[notesIndex], previousCourses: row[prevCoursesIndex], attendance: attendanceMap, attendancePercentage: attendancePercentage.toFixed(1) + '%' };
  });
  // Pass the new teacher names in the return object
    return {
      className: className,
      schedule: schedule,
      students: students,
      levels: LEVELS,
      teacher1: teacher1, // <-- NEW
      teacher2: teacher2,  // <-- NEW
      midPointDate: midPointDate // <-- NEWLY ADDED
    };

  } catch (e) {
    Logger.log(e);
    return { error: 'An error occurred: ' + e.message };
  }
}

function updateStudentCell(sheetName, studentEmail, columnName, newValue) {
  const ss = getSpreadsheet(); // MODIFIED
  const sheet = ss.getSheetByName(sheetName);
  const data = sheet.getDataRange().getValues();
  const headers = data[0];
  const emailColIndex = headers.indexOf('Student Email'), targetColIndex = headers.indexOf(columnName);
  if (targetColIndex === -1) { return `Error: Column "${columnName}" not found.`; }
  for (let i = 1; i < data.length; i++) {
    if (data[i][emailColIndex] === studentEmail) {
      sheet.getRange(i + 1, targetColIndex + 1).setValue(newValue);
      return "Saved";
    }
  }
  return "Error: Student not found.";
}

//function recordAttendance(sheetName, studentEmail, dateString, isPresent) {
// const ss = getSpreadsheet(); // MODIFIED
//  const sheet = ss.getSheetByName(sheetName);
//  const data = sheet.getDataRange().getValues();
//  const headers = data[0];
//  const emailCol = headers.indexOf('Student Email') + 1, datesCol = headers.indexOf('Dates Present') + 1;
//  for (let i = 1; i < data.length; i++) {
//    if (data[i][emailCol - 1] === studentEmail) {
//      let datesPresent = data[i][datesCol - 1] ? data[i][datesCol - 1].toString().split(',').map(d=>d.trim()).filter(Boolean) : [];
//      const dateIndex = datesPresent.indexOf(dateString);
//      if (isPresent && dateIndex === -1) { datesPresent.push(dateString); } else if (!isPresent && dateIndex > -1) { datesPresent.splice(dateIndex, 1); }
//      datesPresent.sort();
//      sheet.getRange(i + 1, datesCol).setValue(datesPresent.join(','));
//      return "Saved";
//    }
//  }
//  return "Error: Student not found.";
// }

// --- NEW MORE ROBUST RECORD ATTENDANCE ---

function recordAttendance(sheetName, studentEmail, dateString, isPresent) {
  const ss = getSpreadsheet();
  const sheet = ss.getSheetByName(sheetName);
  if (!sheet) return "Error: Sheet not found.";
  
  const data = sheet.getDataRange().getValues();
  const headers = data[0];
  const emailCol = headers.indexOf('Student Email');
  const datesCol = headers.indexOf('Dates Present');

  if (emailCol === -1 || datesCol === -1) {
    return "Error: Required columns ('Student Email', 'Dates Present') not found.";
  }

  for (let i = 1; i < data.length; i++) {
    if (data[i][emailCol] === studentEmail) {
      const datesPresentStr = data[i][datesCol] ? data[i][datesCol].toString() : "";
      
      // --- ROBUSTNESS FIX START ---
      // This new logic cleans up existing data and ensures all dates are in the correct format.
      
      const cleanedDates = new Set();
      const rawDates = datesPresentStr.split(',').map(d => d.trim()).filter(Boolean);

      // 1. Process all existing dates, parsing and re-formatting them.
      for (const d of rawDates) {
        try {
          const dateObj = new Date(d);
          // Check if the date is valid. Invalid dates (from bad text) will result in NaN.
          if (!isNaN(dateObj.getTime())) { 
             const yyyy = dateObj.getUTCFullYear();
             const mm = String(dateObj.getUTCMonth() + 1).padStart(2, '0');
             const dd = String(dateObj.getUTCDate()).padStart(2, '0');
             cleanedDates.add(`${yyyy}-${mm}-${dd}`);
          }
        } catch (e) {
          // Ignore dates that can't be parsed
          Logger.log(`Could not parse date '${d}' for student ${studentEmail}. Skipping.`);
        }
      }

      // 2. Add or remove the new date from the cleaned set.
      if (isPresent) {
        cleanedDates.add(dateString);
      } else {
        cleanedDates.delete(dateString);
      }
      
      // 3. Convert the Set back to a sorted array and join to a string.
      const finalDatesArray = Array.from(cleanedDates).sort();
      sheet.getRange(i + 1, datesCol + 1).setValue(finalDatesArray.join(','));
      
      // --- ROBUSTNESS FIX END ---

      return "Saved";
    }
  }
  return "Error: Student not found.";
}

function getConfigData() {
  const ss = getSpreadsheet(); // MODIFIED
  const sheet = ss.getSheetByName('Config');
  return sheet.getDataRange().getValues().map(row => row.join(',')).join('\n');
}

function saveConfigData(csvData) {
  const data = Utilities.parseCsv(csvData);
  const ss = getSpreadsheet(); // MODIFIED
  const sheet = ss.getSheetByName('Config');
  sheet.clearContents();
  sheet.getRange(1, 1, data.length, data[0].length).setValues(data);
  return 'Configuration saved successfully!';
}