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
            $productFields = Capsule::table('tblcustomfields')
                ->where('type', 'product')
                ->where('relid', $pid)
                ->get();
                
            $globalFields = Capsule::table('tblcustomfields')
                ->where('type', 'client')
                ->get();
                
            $fields = collect($productFields)->merge($globalFields);
                
            foreach ($fields as $field) {
                $fieldNameLower = strtolower($field->fieldname);
                if (strpos($fieldNameLower, 'password') !== false || 
                    strpos($fieldNameLower, 'pass') !== false || 
                    strpos($fieldNameLower, 'pwd') !== false) {
                    
                    $value = Capsule::table('tblcustomfieldsvalues')
                        ->where('fieldid', $field->id)
                        ->where('relid', $serviceid)
                        ->value('value');
                    
                    if ($value) {
                        $decryptedValue = decrypt($value);
                        // Convert HTML entities to their actual characters before output
                        $cleanValue = html_entity_decode($decryptedValue, ENT_QUOTES | ENT_HTML5, 'UTF-8');
                        header('Content-Type: text/plain');
                        die($cleanValue ?? 'No password value');
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
            white-space: pre;
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
            
            const rows = document.querySelectorAll("#additionalinfo .row, .custom-field-row");
            rows.forEach(row => {
                const label = row.querySelector("strong, .field-label");
                if (label && /password|pass|pwd/i.test(label.textContent.trim())) {
                    console.log("Found password row:", label.textContent.trim());
                    
                    const valueCell = row.querySelector(".col-sm-7, .field-value");
                    if (valueCell) {
                        console.log("Found value cell");
                        
                        const container = document.createElement("div");
                        container.className = "password-container";
                        container.style.position = "relative";
                        
                        const passwordSpan = document.createElement("pre");
                        passwordSpan.className = "password-field";
                        passwordSpan.style.margin = "0";
                        passwordSpan.style.display = "inline";
                        passwordSpan.textContent = "******";
                        
                        const toggleBtn = document.createElement("button");
                        toggleBtn.type = "button";
                        toggleBtn.className = "password-toggle";
                        toggleBtn.textContent = "Show";

                        const copyBtn = document.createElement("button");
                        copyBtn.type = "button";
                        copyBtn.className = "password-copy";
                        copyBtn.textContent = "Copy";
                        copyBtn.style.display = "none";

                        const tooltip = document.createElement("span");
                        tooltip.className = "copy-tooltip";
                        tooltip.textContent = "Copied!";
                        tooltip.style.opacity = "0";
                        
                        copyBtn.addEventListener("click", async function(e) {
                            e.preventDefault();
                            e.stopPropagation();
                            
                            try {
                                await navigator.clipboard.writeText(passwordSpan.getAttribute("data-raw-password") || passwordSpan.textContent);
                                tooltip.style.opacity = "1";
                                setTimeout(() => {
                                    tooltip.style.opacity = "0";
                                }, 1000);
                            } catch (err) {
                                console.error("Failed to copy text: ", err);
                            }
                        });

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
                                        passwordSpan.setAttribute("data-raw-password", password);
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
                        
                        container.appendChild(passwordSpan);
                        container.appendChild(toggleBtn);
                        container.appendChild(copyBtn);
                        container.appendChild(tooltip);
                        
                        valueCell.innerHTML = "";
                        valueCell.appendChild(container);
                        
                        console.log("Password field modified successfully");
                    }
                }
            });
        }

        if (document.readyState === "loading") {
            document.addEventListener("DOMContentLoaded", modifyPasswordFields);
        } else {
            modifyPasswordFields();
        }

        setTimeout(modifyPasswordFields, 1000);
    </script>';
}

add_hook('ClientAreaFooterOutput', 1, 'passwordtoggleplus_show_passwords');
