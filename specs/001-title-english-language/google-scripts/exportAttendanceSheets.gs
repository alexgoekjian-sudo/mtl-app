/**
 * MTL Attendance Export Script
 * 
 * Exports all attendance sheets from Google Sheets workbook to CSV files
 * Each sheet name should match the attendance_id from course_offerings table
 * 
 * Usage:
 * 1. Open your Google Sheets workbook
 * 2. Go to Extensions > Apps Script
 * 3. Paste this code
 * 4. Run exportAllSheets() function
 * 5. Grant permissions when prompted
 * 6. CSV files will be created in a "MTL_Attendance_Export" folder in your Google Drive
 */

function exportAllSheets() {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheets = ss.getSheets();
  
  // Create export folder in Google Drive
  const folderName = 'MTL_Attendance_Export_' + Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd_HHmmss');
  const folder = DriveApp.createFolder(folderName);
  
  let exportLog = [];
  
  sheets.forEach(sheet => {
    const sheetName = sheet.getName();
    
    // Skip sheets that don't look like attendance IDs (you can adjust this filter)
    if (sheetName.toLowerCase().includes('template') || 
        sheetName.toLowerCase().includes('master') ||
        sheetName.toLowerCase().includes('summary')) {
      Logger.log(`Skipping sheet: ${sheetName}`);
      return;
    }
    
    try {
      const csvContent = convertSheetToCSV(sheet);
      const fileName = `${sheetName}.csv`;
      
      // Create CSV file in the folder
      const file = folder.createFile(fileName, csvContent, MimeType.CSV);
      
      exportLog.push({
        sheet: sheetName,
        status: 'Success',
        file: file.getUrl(),
        rows: sheet.getLastRow() - 1 // Exclude header
      });
      
      Logger.log(`Exported: ${sheetName} (${sheet.getLastRow() - 1} students)`);
    } catch (error) {
      exportLog.push({
        sheet: sheetName,
        status: 'Error',
        error: error.toString()
      });
      Logger.log(`Error exporting ${sheetName}: ${error}`);
    }
  });
  
  // Create summary file
  createSummaryFile(folder, exportLog);
  
  // Show completion message
  const ui = SpreadsheetApp.getUi();
  ui.alert(
    'Export Complete',
    `Exported ${exportLog.filter(l => l.status === 'Success').length} attendance sheets.\n\n` +
    `Folder: ${folderName}\n` +
    `Link: ${folder.getUrl()}`,
    ui.ButtonSet.OK
  );
  
  Logger.log(`Export folder: ${folder.getUrl()}`);
}

/**
 * Convert a sheet to CSV format
 */
function convertSheetToCSV(sheet) {
  const data = sheet.getDataRange().getValues();
  
  // Expected columns from your attendance sheets
  const expectedHeaders = [
    'Student Name',
    'Student Email', 
    'Dates Present',
    'Initial Level',
    'Mid Level',
    'Teacher Notes',
    'Previous Courses',
    'Trello Card ID',
    'Country'
  ];
  
  const csvRows = [];
  
  data.forEach((row, index) => {
    if (index === 0) {
      // Header row - use expected headers
      csvRows.push(expectedHeaders.join(','));
    } else {
      // Data rows - escape and quote fields
      const csvRow = row.map(cell => {
        let value = cell !== null && cell !== undefined ? cell.toString() : '';
        
        // Escape quotes and wrap in quotes if contains comma, quote, or newline
        if (value.includes(',') || value.includes('"') || value.includes('\n')) {
          value = '"' + value.replace(/"/g, '""') + '"';
        }
        
        return value;
      }).join(',');
      
      csvRows.push(csvRow);
    }
  });
  
  return csvRows.join('\n');
}

/**
 * Create a summary file with export details
 */
function createSummaryFile(folder, exportLog) {
  let summary = 'MTL Attendance Export Summary\n';
  summary += '================================\n';
  summary += `Export Date: ${new Date().toISOString()}\n`;
  summary += `Total Sheets Processed: ${exportLog.length}\n`;
  summary += `Successful Exports: ${exportLog.filter(l => l.status === 'Success').length}\n`;
  summary += `Failed Exports: ${exportLog.filter(l => l.status === 'Error').length}\n\n`;
  
  summary += 'Details:\n';
  summary += '--------\n';
  
  exportLog.forEach(log => {
    summary += `\nSheet: ${log.sheet}\n`;
    summary += `Status: ${log.status}\n`;
    if (log.rows !== undefined) {
      summary += `Students: ${log.rows}\n`;
      summary += `File: ${log.file}\n`;
    }
    if (log.error) {
      summary += `Error: ${log.error}\n`;
    }
  });
  
  folder.createFile('_EXPORT_SUMMARY.txt', summary, MimeType.PLAIN_TEXT);
}

/**
 * Export a single sheet by name
 */
function exportSingleSheet(sheetName) {
  const ss = SpreadsheetApp.getActiveSpreadsheet();
  const sheet = ss.getSheetByName(sheetName);
  
  if (!sheet) {
    throw new Error(`Sheet "${sheetName}" not found`);
  }
  
  const csvContent = convertSheetToCSV(sheet);
  const fileName = `${sheetName}.csv`;
  
  // Create in root of Drive
  const file = DriveApp.createFile(fileName, csvContent, MimeType.CSV);
  
  Logger.log(`Exported: ${file.getUrl()}`);
  return file.getUrl();
}

/**
 * Create menu in Google Sheets
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('MTL Attendance')
    .addItem('Export All Sheets', 'exportAllSheets')
    .addItem('Export Current Sheet', 'exportCurrentSheet')
    .addToUi();
}

/**
 * Export just the current active sheet
 */
function exportCurrentSheet() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const sheetName = sheet.getName();
  
  const folderName = 'MTL_Attendance_Export_' + Utilities.formatDate(new Date(), Session.getScriptTimeZone(), 'yyyy-MM-dd_HHmmss');
  const folder = DriveApp.createFolder(folderName);
  
  const csvContent = convertSheetToCSV(sheet);
  const fileName = `${sheetName}.csv`;
  const file = folder.createFile(fileName, csvContent, MimeType.CSV);
  
  const ui = SpreadsheetApp.getUi();
  ui.alert(
    'Export Complete',
    `Exported: ${sheetName}\n` +
    `Students: ${sheet.getLastRow() - 1}\n\n` +
    `File: ${file.getUrl()}`,
    ui.ButtonSet.OK
  );
}
