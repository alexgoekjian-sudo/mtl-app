/**
 * Extract Email from Description Column
 * 
 * Checks if 'email' column is empty, and if so, extracts the first email
 * found in the 'description' column and places it in 'email_check' column.
 * 
 * Usage:
 *   1. Open Google Sheets with your Trello export
 *   2. Tools → Script editor
 *   3. Paste this code
 *   4. Run 'onOpen' to add custom menu
 *   5. Use "Email Extraction" → "Extract Emails from Description"
 */

/**
 * Adds custom menu to Google Sheets
 */
function onOpen() {
  const ui = SpreadsheetApp.getUi();
  ui.createMenu('Email Extraction')
    .addItem('Extract Emails from Description', 'extractEmailsFromDescription')
    .addItem('Check for Missing Emails', 'checkMissingEmails')
    .addToUi();
}

/**
 * Main function: Extract emails from description column
 */
function extractEmailsFromDescription() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const ui = SpreadsheetApp.getUi();
  
  // Get all data
  const dataRange = sheet.getDataRange();
  const data = dataRange.getValues();
  
  if (data.length === 0) {
    ui.alert('Error', 'Sheet is empty', ui.ButtonSet.OK);
    return;
  }
  
  // Find column indices
  const headers = data[0];
  const emailCol = findColumnIndex(headers, ['email', 'Email', 'Email address', 'Email Address']);
  const descCol = findColumnIndex(headers, ['description', 'Description', 'Desc', 'desc']);
  let emailCheckCol = findColumnIndex(headers, ['email_check', 'Email Check', 'EmailCheck']);
  
  // Validate required columns exist
  if (emailCol === -1) {
    ui.alert('Error', 'Could not find "email" column', ui.ButtonSet.OK);
    return;
  }
  
  if (descCol === -1) {
    ui.alert('Error', 'Could not find "description" column', ui.ButtonSet.OK);
    return;
  }
  
  // Create email_check column if it doesn't exist
  if (emailCheckCol === -1) {
    emailCheckCol = headers.length;
    sheet.getRange(1, emailCheckCol + 1).setValue('email_check');
    Logger.log('Created new column: email_check');
  }
  
  // Process each row
  let processedCount = 0;
  let emailsFound = 0;
  let emailsNotFound = 0;
  
  for (let i = 1; i < data.length; i++) { // Start at 1 to skip header
    const emailValue = data[i][emailCol];
    const descValue = data[i][descCol];
    
    // Check if email column is empty
    if (isEmptyCell(emailValue)) {
      processedCount++;
      
      if (descValue && typeof descValue === 'string') {
        // Extract first email from description
        const extractedEmail = extractFirstEmail(descValue);
        
        if (extractedEmail) {
          // Write to email_check column
          sheet.getRange(i + 1, emailCheckCol + 1).setValue(extractedEmail);
          emailsFound++;
          Logger.log(`Row ${i + 1}: Found email "${extractedEmail}" in description`);
        } else {
          emailsNotFound++;
          Logger.log(`Row ${i + 1}: No email found in description`);
        }
      } else {
        emailsNotFound++;
        Logger.log(`Row ${i + 1}: Description is empty`);
      }
    }
  }
  
  // Show summary
  const message = `Processing complete!\n\n` +
                  `Rows with empty email: ${processedCount}\n` +
                  `Emails extracted: ${emailsFound}\n` +
                  `No email found: ${emailsNotFound}\n\n` +
                  `Results written to "email_check" column.`;
  
  ui.alert('Email Extraction Complete', message, ui.ButtonSet.OK);
  Logger.log(message);
}

/**
 * Extract first email address from text using regex
 * Handles line breaks and multi-line text
 */
function extractFirstEmail(text) {
  if (!text || typeof text !== 'string') {
    return null;
  }
  
  // Email regex pattern
  // Matches standard email format: user@domain.tld
  const emailPattern = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/;
  
  // Remove common non-email artifacts
  text = text.replace(/mailto:/gi, '');
  
  // Search for email
  const match = text.match(emailPattern);
  
  if (match && match[0]) {
    return match[0].trim().toLowerCase();
  }
  
  return null;
}

/**
 * Check if cell is empty (handles various empty states)
 */
function isEmptyCell(value) {
  return value === null || 
         value === undefined || 
         value === '' || 
         (typeof value === 'string' && value.trim() === '');
}

/**
 * Find column index by matching multiple possible header names
 */
function findColumnIndex(headers, possibleNames) {
  for (let i = 0; i < headers.length; i++) {
    const header = headers[i];
    if (header && typeof header === 'string') {
      const normalizedHeader = header.trim().toLowerCase();
      for (const name of possibleNames) {
        if (normalizedHeader === name.toLowerCase()) {
          return i;
        }
      }
    }
  }
  return -1;
}

/**
 * Utility function: Check which rows have missing emails
 * (For diagnostics)
 */
function checkMissingEmails() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const ui = SpreadsheetApp.getUi();
  
  const dataRange = sheet.getDataRange();
  const data = dataRange.getValues();
  
  if (data.length === 0) {
    ui.alert('Error', 'Sheet is empty', ui.ButtonSet.OK);
    return;
  }
  
  const headers = data[0];
  const emailCol = findColumnIndex(headers, ['email', 'Email', 'Email address']);
  
  if (emailCol === -1) {
    ui.alert('Error', 'Could not find "email" column', ui.ButtonSet.OK);
    return;
  }
  
  let missingCount = 0;
  const missingRows = [];
  
  for (let i = 1; i < data.length; i++) {
    if (isEmptyCell(data[i][emailCol])) {
      missingCount++;
      missingRows.push(i + 1); // +1 for 1-indexed rows
    }
  }
  
  if (missingCount === 0) {
    ui.alert('Check Complete', 'All rows have email addresses!', ui.ButtonSet.OK);
  } else {
    const rowList = missingRows.slice(0, 20).join(', ');
    const more = missingCount > 20 ? ` (and ${missingCount - 20} more)` : '';
    const message = `Found ${missingCount} rows with missing emails.\n\n` +
                    `Rows: ${rowList}${more}`;
    ui.alert('Missing Emails Found', message, ui.ButtonSet.OK);
  }
  
  Logger.log(`Missing emails: ${missingCount} rows`);
}

/**
 * Advanced: Extract ALL emails from description (not just first)
 * Useful if you want to see all email addresses in a field
 */
function extractAllEmailsFromDescription() {
  const sheet = SpreadsheetApp.getActiveSheet();
  const ui = SpreadsheetApp.getUi();
  
  const dataRange = sheet.getDataRange();
  const data = dataRange.getValues();
  
  if (data.length === 0) {
    ui.alert('Error', 'Sheet is empty', ui.ButtonSet.OK);
    return;
  }
  
  const headers = data[0];
  const descCol = findColumnIndex(headers, ['description', 'Description']);
  let allEmailsCol = findColumnIndex(headers, ['all_emails', 'All Emails']);
  
  if (descCol === -1) {
    ui.alert('Error', 'Could not find "description" column', ui.ButtonSet.OK);
    return;
  }
  
  // Create all_emails column if doesn't exist
  if (allEmailsCol === -1) {
    allEmailsCol = headers.length;
    sheet.getRange(1, allEmailsCol + 1).setValue('all_emails');
  }
  
  // Process each row
  for (let i = 1; i < data.length; i++) {
    const descValue = data[i][descCol];
    
    if (descValue && typeof descValue === 'string') {
      const emails = extractAllEmails(descValue);
      if (emails.length > 0) {
        sheet.getRange(i + 1, allEmailsCol + 1).setValue(emails.join(', '));
      }
    }
  }
  
  ui.alert('Complete', 'All emails extracted to "all_emails" column', ui.ButtonSet.OK);
}

/**
 * Extract ALL email addresses from text
 */
function extractAllEmails(text) {
  if (!text || typeof text !== 'string') {
    return [];
  }
  
  const emailPattern = /\b[A-Za-z0-9._%+-]+@[A-Za-z0-9.-]+\.[A-Z|a-z]{2,}\b/g;
  text = text.replace(/mailto:/gi, '');
  
  const matches = text.match(emailPattern);
  
  if (matches) {
    // Deduplicate and normalize
    const uniqueEmails = [...new Set(matches.map(e => e.trim().toLowerCase()))];
    return uniqueEmails;
  }
  
  return [];
}
