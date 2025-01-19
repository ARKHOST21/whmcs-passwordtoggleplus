<?php

use WHMCS\Database\Capsule;

if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}

function passwordtoggleplus_show_passwords($vars) {
    if (!isset($_GET['action']) || $_GET['action'] !== 'productdetails') {
        return;
    }

    $serviceid = (int)$_GET['id'];

    if (isset($_GET['getpassword'])) {
        $pid = Capsule::table('tblhosting')
            ->where('id', $serviceid)
            ->value('packageid');
            
        if ($pid) {
            // Get product-specific custom fields
            $productFields = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $pid)
                ->get();
                
            // Get global custom fields
            $globalFields = Capsule::table('tblcustomfields')
                ->where('type', 'client')
                ->get();
                
            // Combine both sets of fields
            $fields = collect($productFields)->merge($globalFields);
                
            foreach ($fields as $field) {
                // Check for various password-related field names
                $fieldNameLower = strtolower($field->fieldname);
                if (strpos($fieldNameLower, 'password') !== false || 
                    strpos($fieldNameLower, 'pass') !== false || 
                    strpos($fieldNameLower, 'pwd') !== false) {
                    
                    $value = Capsule::table('tblcustomfieldsvalues')
                        ->where('fieldid', $field->id)
                        ->where('relid', $serviceid)
                        ->value('value');
                    
                    if ($value) {
                        // Decrypt the password value
                        $decryptedValue = decrypt($value);
                        die($decryptedValue ?? 'No password value');
                    }
                }
            }
        }
        die('No password field found');
    }

    return '
    <style>
        .password-container {
            display: flex;
            align-items: center;
            gap: 10px;
        }
        .password-toggle, .password-copy {
            padding: 3px 8px;
            border: 1px solid #ddd;
            border-radius: 4px;
            background: #f8f9fa;
            cursor: pointer;
            font-size: 12px;
        }
        .password-toggle:hover, .password-copy:hover {
            background: #e9ecef;
        }
        .password-field {
            font-family: monospace;
        }
        .copy-tooltip {
            position: absolute;
            background: rgba(0,0,0,0.8);
            color: white;
            padding: 4px 8px;
            border-radius: 4px;
            font-size: 12px;
            pointer-events: none;
            opacity: 0;
            transition: opacity 0.2s;
            transform: translateY(-25px);
        }
    </style>
    <script>
        function modifyPasswordFields() {
            console.log("Starting password field modification...");
            
            // Find password fields in both additional info and custom fields sections
            const rows = document.querySelectorAll("#additionalinfo .row, .custom-field-row");
            rows.forEach(row => {
                const label = row.querySelector("strong, .field-label");
                if (label && /password|pass|pwd/i.test(label.textContent.trim())) {
                    console.log("Found password row:", label.textContent.trim());
                    
                    // Get the value cell
                    const valueCell = row.querySelector(".col-sm-7, .field-value");
                    if (valueCell) {
                        console.log("Found value cell");
                        
                        // Create new elements
                        const container = document.createElement("div");
                        container.className = "password-container";
                        container.style.position = "relative";
                        
                        const passwordSpan = document.createElement("span");
                        passwordSpan.className = "password-field";
                        passwordSpan.textContent = "******";
                        
                        const toggleBtn = document.createElement("button");
                        toggleBtn.type = "button";
                        toggleBtn.className = "password-toggle";
                        toggleBtn.textContent = "Show";

                        const copyBtn = document.createElement("button");
                        copyBtn.type = "button";
                        copyBtn.className = "password-copy";
                        copyBtn.textContent = "Copy";
                        copyBtn.style.display = "none"; // Hide initially

                        const tooltip = document.createElement("span");
                        tooltip.className = "copy-tooltip";
                        tooltip.textContent = "Copied!";
                        tooltip.style.opacity = "0";
                        
                        // Add copy functionality
                        copyBtn.addEventListener("click", async function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            const textToCopy = passwordSpan.textContent;
                            try {
                                await navigator.clipboard.writeText(textToCopy);
                                tooltip.style.opacity = "1";
                                setTimeout(() => {
                                    tooltip.style.opacity = "0";
                                }, 1000);
                            } catch (err) {
                                console.error("Failed to copy text: ", err);
                            }
                        });

                        // Add toggle handler
                        toggleBtn.addEventListener("click", function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            if (toggleBtn.textContent === "Show") {
                                const url = window.location.href + (window.location.href.includes("?") ? "&" : "?") + "getpassword=1";
                                fetch(url)
                                    .then(response => response.text())
                                    .then(password => {
                                        if (password === "No password field found" || password === "No password value") {
                                            passwordSpan.textContent = "No password available";
                                            copyBtn.style.display = "none";
                                            return;
                                        }
                                        passwordSpan.textContent = password;
                                        toggleBtn.textContent = "Hide";
                                        copyBtn.style.display = "inline-block";
                                    })
                                    .catch(error => {
                                        console.error("Error:", error);
                                        passwordSpan.textContent = "Error loading password";
                                        copyBtn.style.display = "none";
                                    });
                            } else {
                                passwordSpan.textContent = "******";
                                toggleBtn.textContent = "Show";
                                copyBtn.style.display = "none";
                            }
                            return false;
                        });
                        
                        // Assemble and insert
                        container.appendChild(passwordSpan);
                        container.appendChild(toggleBtn);
                        container.appendChild(copyBtn);
                        container.appendChild(tooltip);
                        
                        // Clear and update the value cell
                        valueCell.innerHTML = "";
                        valueCell.appendChild(container);
                        
                        console.log("Password field modified successfully");
                    }
                }
            });
        }

        // Run the function when DOM is ready
        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", modifyPasswordFields);
        } else {
            modifyPasswordFields();
        }

        // Run again after a delay to catch dynamic content
        setTimeout(modifyPasswordFields, 1000);
    </script>';
}

// Register the hook
add_hook('ClientAreaFooterOutput', 1, 'passwordtoggleplus_show_passwords');
