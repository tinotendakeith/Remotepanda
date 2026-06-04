<?php

if (!function_exists('rp_template_value')) {
    function rp_template_value($value)
    {
        if ($value === null) {
            return '';
        }

        if (is_array($value)) {
            foreach (array('name', 'username', 'full_name', 'display_name') as $key) {
                if (isset($value[$key]) && !is_array($value[$key])) {
                    return trim((string) $value[$key]);
                }
            }

            return '';
        }

        return trim((string) $value);
    }
}

if (!function_exists('rp_template_placeholder_catalog')) {
    function rp_template_placeholder_catalog()
    {
        return array(
            array('tag' => '{{patient_name}}', 'key' => 'patient_name', 'label' => 'Patient Name'),
            array('tag' => '{{exam_name}}', 'key' => 'exam_name', 'label' => 'Exam / Study'),
            array('tag' => '{{exam_date}}', 'key' => 'exam_date', 'label' => 'Exam Date'),
            array('tag' => '{{date_of_birth}}', 'key' => 'date_of_birth', 'label' => 'Date of Birth'),
            array('tag' => '{{referrer}}', 'key' => 'referrer', 'label' => 'Referrer'),
            array('tag' => '{{gender}}', 'key' => 'gender', 'label' => 'Gender'),
            array('tag' => '{{study_id}}', 'key' => 'study_id', 'label' => 'Study ID'),
            array('tag' => '{{radiographer_name}}', 'key' => 'radiographer_name', 'label' => 'Radiographer'),
            array('tag' => '{{radiologist_name}}', 'key' => 'radiologist_name', 'label' => 'Radiologist'),
            array('tag' => '{{today_date}}', 'key' => 'today_date', 'label' => 'Today Date')
        );
    }
}

if (!function_exists('rp_template_supported_upload_extensions')) {
    function rp_template_supported_upload_extensions()
    {
        return array('htm', 'html', 'docx');
    }
}

if (!function_exists('rp_template_editable_extensions')) {
    function rp_template_editable_extensions()
    {
        return array('htm', 'html');
    }
}

if (!function_exists('rp_template_context_from_records')) {
    function rp_template_context_from_records($study, $event, $currentUser)
    {
        $patientName = rp_template_value($study['Name'] ?? ($event['Name'] ?? ''));
        $examName = rp_template_value($study['requested_procedure'] ?? ($event['study'] ?? ''));
        $examDate = rp_template_value($event['start'] ?? ($study['start_date'] ?? ''));
        $dateOfBirth = rp_template_value($study['date_of_birth'] ?? '');
        $referrer = rp_template_value($study['requesting_physician'] ?? ($event['referring_doctor'] ?? ''));
        $gender = rp_template_value($study['gender'] ?? '');
        $studyId = rp_template_value($study['study_id'] ?? ($event['id'] ?? ''));
        $radiographerName = rp_template_value($currentUser);
        $radiologistName = rp_template_value($currentUser);

        return array(
            'patient_name' => $patientName,
            'exam_name' => $examName,
            'exam_date' => $examDate,
            'date_of_birth' => $dateOfBirth,
            'referrer' => $referrer,
            'gender' => $gender,
            'study_id' => $studyId,
            'radiographer_name' => $radiographerName,
            'radiologist_name' => $radiologistName,
            'today_date' => date('Y-m-d')
        );
    }
}

if (!function_exists('rp_render_template_placeholders')) {
    function rp_render_template_placeholders($content, $context)
    {
        $content = (string) $content;
        if ($content === '') {
            return '';
        }

        $replace = array();
        foreach (rp_template_placeholder_catalog() as $item) {
            $replace[$item['tag']] = rp_template_value($context[$item['key']] ?? '');
        }

        return strtr($content, $replace);
    }
}

if (!function_exists('rp_template_safe_base_name')) {
    function rp_template_safe_base_name($name)
    {
        $base = preg_replace('/[^A-Za-z0-9 _-]/', '', (string) $name);
        $base = trim((string) $base);
        if ($base === '') {
            $base = 'Template_' . time();
        }
        return $base;
    }
}

if (!function_exists('rp_template_render_docx_run')) {
    function rp_template_render_docx_run(DOMNode $runNode)
    {
        $html = '';
        $isBold = false;
        $isItalic = false;
        $isUnderline = false;

        foreach ($runNode->childNodes as $child) {
            if ($child->nodeType !== XML_ELEMENT_NODE) {
                continue;
            }

            $localName = $child->localName;
            if ($localName === 'rPr') {
                foreach ($child->childNodes as $prop) {
                    if ($prop->nodeType !== XML_ELEMENT_NODE) {
                        continue;
                    }
                    if ($prop->localName === 'b') {
                        $isBold = true;
                    } elseif ($prop->localName === 'i') {
                        $isItalic = true;
                    } elseif ($prop->localName === 'u') {
                        $isUnderline = true;
                    }
                }
                continue;
            }

            if ($localName === 't') {
                $html .= htmlspecialchars((string) $child->textContent, ENT_QUOTES, 'UTF-8');
            } elseif ($localName === 'tab') {
                $html .= '&nbsp;&nbsp;&nbsp;&nbsp;';
            } elseif ($localName === 'br' || $localName === 'cr') {
                $html .= '<br>';
            }
        }

        if ($html === '') {
            return '';
        }
        if ($isUnderline) {
            $html = '<u>' . $html . '</u>';
        }
        if ($isItalic) {
            $html = '<em>' . $html . '</em>';
        }
        if ($isBold) {
            $html = '<strong>' . $html . '</strong>';
        }

        return $html;
    }
}

if (!function_exists('rp_template_render_docx_node')) {
    function rp_template_render_docx_node(DOMNode $node)
    {
        if ($node->nodeType !== XML_ELEMENT_NODE) {
            return '';
        }

        $name = $node->localName;
        if ($name === 'p') {
            $inner = '';
            $tag = 'p';
            foreach ($node->childNodes as $child) {
                if ($child->nodeType !== XML_ELEMENT_NODE) {
                    continue;
                }
                if ($child->localName === 'pPr') {
                    foreach ($child->childNodes as $prop) {
                        if ($prop->nodeType === XML_ELEMENT_NODE && $prop->localName === 'pStyle') {
                            $styleVal = '';
                            if ($prop->attributes) {
                                foreach ($prop->attributes as $attr) {
                                    if (strtolower((string) $attr->localName) === 'val') {
                                        $styleVal = strtolower((string) $attr->nodeValue);
                                        break;
                                    }
                                }
                            }
                            if (strpos($styleVal, 'heading1') !== false) {
                                $tag = 'h1';
                            } elseif (strpos($styleVal, 'heading2') !== false) {
                                $tag = 'h2';
                            } elseif (strpos($styleVal, 'heading3') !== false) {
                                $tag = 'h3';
                            }
                        }
                    }
                    continue;
                }
                if ($child->localName === 'r' || $child->localName === 'hyperlink') {
                    if ($child->localName === 'hyperlink') {
                        foreach ($child->childNodes as $hyperChild) {
                            if ($hyperChild->nodeType === XML_ELEMENT_NODE && $hyperChild->localName === 'r') {
                                $inner .= rp_template_render_docx_run($hyperChild);
                            }
                        }
                    } else {
                        $inner .= rp_template_render_docx_run($child);
                    }
                }
            }
            $innerTrimmed = trim(str_replace('&nbsp;', ' ', strip_tags($inner)));
            if ($innerTrimmed === '' && strpos($inner, '<br>') === false) {
                $inner = '&nbsp;';
            }
            return '<' . $tag . '>' . $inner . '</' . $tag . '>';
        }

        if ($name === 'tbl') {
            $rows = '';
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tr') {
                    $rows .= rp_template_render_docx_node($child);
                }
            }
            return '<table border="1" cellspacing="0" cellpadding="6" style="border-collapse:collapse;width:100%;">' . $rows . '</table>';
        }

        if ($name === 'tr') {
            $cells = '';
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE && $child->localName === 'tc') {
                    $cells .= rp_template_render_docx_node($child);
                }
            }
            return '<tr>' . $cells . '</tr>';
        }

        if ($name === 'tc') {
            $inner = '';
            foreach ($node->childNodes as $child) {
                if ($child->nodeType === XML_ELEMENT_NODE) {
                    $inner .= rp_template_render_docx_node($child);
                }
            }
            return '<td>' . $inner . '</td>';
        }

        return '';
    }
}

if (!function_exists('rp_template_docx_to_html')) {
    function rp_template_docx_to_html($docxPath, &$error = '')
    {
        $error = '';
        if (!class_exists('ZipArchive')) {
            $error = 'DOCX import requires ZipArchive support on the server.';
            return '';
        }

        $zip = new ZipArchive();
        if ($zip->open($docxPath) !== true) {
            $error = 'Could not open the DOCX file.';
            return '';
        }

        $documentXml = $zip->getFromName('word/document.xml');
        $zip->close();
        if ($documentXml === false || trim((string) $documentXml) === '') {
            $error = 'The DOCX file does not contain readable document content.';
            return '';
        }

        $dom = new DOMDocument();
        $loaded = @$dom->loadXML($documentXml);
        if (!$loaded) {
            $error = 'Could not parse the DOCX document content.';
            return '';
        }

        $bodyNodes = $dom->getElementsByTagNameNS('http://schemas.openxmlformats.org/wordprocessingml/2006/main', 'body');
        if ($bodyNodes->length === 0) {
            $error = 'DOCX body content is missing.';
            return '';
        }

        $html = '';
        foreach ($bodyNodes->item(0)->childNodes as $child) {
            if ($child->nodeType === XML_ELEMENT_NODE) {
                $html .= rp_template_render_docx_node($child);
            }
        }

        $html = trim((string) $html);
        if ($html === '') {
            $error = 'No usable text could be extracted from this DOCX template.';
            return '';
        }

        return '<div>' . $html . '</div>';
    }
}

if (!function_exists('rp_template_import_upload_to_html')) {
    function rp_template_import_upload_to_html($tmpPath, $originalName, &$error = '')
    {
        $error = '';
        $ext = strtolower(pathinfo((string) $originalName, PATHINFO_EXTENSION));
        if (!in_array($ext, rp_template_supported_upload_extensions(), true)) {
            $error = 'Only .htm, .html, or .docx files are allowed.';
            return '';
        }

        if ($ext === 'htm' || $ext === 'html') {
            $content = @file_get_contents($tmpPath);
            if ($content === false) {
                $error = 'Could not read uploaded HTML template.';
                return '';
            }
            return (string) $content;
        }

        return rp_template_docx_to_html($tmpPath, $error);
    }
}
