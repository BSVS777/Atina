<!DOCTYPE html>
<html lang="es">

<head>
    <meta charset="utf-8">
    <title>{{ __('Report of :title', ['title' => $title]) }} &middot; UTN</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #fff;
            font-family: 'DejaVu Sans', sans-serif;
            color: #24282e;
            padding: 0;
        }

        .page {
            width: auto;
            margin: 0 auto;
            background: #fff;
            overflow: hidden;
        }

        /*
          DOMPDF has no CSS Flexbox support, so this three-column header
          (logo / centered title / tag) uses the CSS table display model
          instead — display:table/table-row/table-cell is the one layout
          primitive DOMPDF renders reliably for "columns side by side,
          vertically centered" without Flexbox or Grid.
        */
        .report-header {
            display: table;
            width: 100%;
            background: #0f2547;
            padding: 16px 0.6in;
            border-bottom: 4px solid #9fb7e0;
        }

        .header-row {
            display: table-row;
        }

        .logo-badge {
            display: table-cell;
            width: 1%;
            white-space: nowrap;
            vertical-align: middle;
            background: #fff;
            border-radius: 10px;
            padding: 6px 10px;
            line-height: 0;
        }

        .logo-badge img {
            height: 30px;
            width: auto;
            display: block;
        }

        .header-titles {
            display: table-cell;
            vertical-align: middle;
            text-align: center;
        }

        .header-title {
            font-family: 'DejaVu Sans', sans-serif;
            font-weight: 800;
            font-size: 15px;
            letter-spacing: 0.08em;
            color: #fff;
            text-transform: uppercase;
        }

        .header-subtitle {
            font-family: 'DejaVu Sans', sans-serif;
            font-weight: 600;
            font-size: 10.5px;
            letter-spacing: 0.06em;
            color: #9fb7e0;
            margin-top: 3px;
            text-transform: uppercase;
        }

        .header-tag {
            display: table-cell;
            width: 1%;
            white-space: nowrap;
            vertical-align: middle;
            font-family: 'DejaVu Sans', sans-serif;
            font-weight: 700;
            font-size: 10.5px;
            letter-spacing: 0.04em;
            color: #fff;
            background: #1c3f77;
            border-radius: 5px;
            padding: 6px 12px;
            text-transform: uppercase;
        }

        .content {
            position: relative;
            padding: 0 0.6in 0.6in;
        }

        .dots-accent {
            position: absolute;
            top: 34px;
            right: 0.6in;
            width: 120px;
            height: 70px;
            background-image: radial-gradient(#c7d7ef 2.5px, transparent 2.5px);
            background-size: 14px 14px;
            opacity: 0.9;
        }

        .hero {
            padding: 36px 0 26px;
            border-bottom: 3px solid #0f2547;
            position: relative;
        }

        .hero h1 {
            font-family: 'DejaVu Sans', sans-serif;
            font-weight: 900;
            font-size: 34px;
            line-height: 1.1;
            color: #0f2547;
            margin: 0;
            max-width: 520px;
        }

        /*
          No Flexbox in DOMPDF: the label/value pair is laid out as two
          inline-block spans instead of an inline-flex row, with an
          explicit margin standing in for `gap` (also unsupported outside
          flex/grid).
        */
        .date-pill {
            display: inline-block;
            margin-top: 18px;
            background: #eef2fa;
            border: 1px solid #c7d7ef;
            border-radius: 20px;
            padding: 6px 16px 6px 6px;
        }

        .date-label {
            display: inline-block;
            vertical-align: middle;
            background: #0f2547;
            color: #fff;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.05em;
            text-transform: uppercase;
            border-radius: 14px;
            padding: 3px 9px;
        }

        .date-value {
            display: inline-block;
            vertical-align: middle;
            margin-left: 8px;
            font-size: 12px;
            color: #3a4252;
        }

        .table-card {
            margin-top: 26px;
            border-radius: 10px;
            border: 1px solid #d7dce6;
            overflow: hidden;
            box-shadow: 0 4px 18px rgba(15, 37, 71, 0.08);
        }

        table {
            border-collapse: collapse;
            width: 100%;
            table-layout: fixed;
            font-family: 'DejaVu Sans', sans-serif;
        }

        th {
            background: #0f2547;
            color: #fff;
            padding: 10px 8px;
            font-size: 9.5px;
            font-weight: 700;
            letter-spacing: 0.03em;
            text-align: left;
            text-transform: uppercase;
        }

        th.num,
        td.num {
            text-align: right;
        }

        td {
            padding: 9px 8px;
            font-size: 10.5px;
            border-bottom: 1px solid #e4e8ef;
        }

        td.row-index {
            color: #8a92a0;
        }

        tbody tr:nth-child(even) {
            background: #f6f8fc;
        }

        tbody tr:nth-child(odd) {
            background: #fff;
        }

        .empty-row td {
            text-align: center;
            color: #8a92a0;
            font-style: italic;
            padding: 22px 8px;
        }

        @page {
            size: letter;
            margin: 0.6in;
        }
    </style>
</head>

<body>

    <div class="page">

        <header class="report-header">
            <div class="header-row">
                <div class="logo-badge"><img src="data:image/png;base64,iVBORw0KGgoAAAANSUhEUgAAAMgAAACaCAYAAADsHgdiAAAACXBIWXMAAA7EAAAOxAGVKw4bAAAgAElEQVR42uy9e3hcV3X3/1n7nBmNZFmWZVu2Zcex48RxLk1CyAUCFAgBCuWBUFquKZS2QElo+/YGBMrLjxcohZZSSMOlfX8FUgqFtpCkQEsJKaUhEBMgNxLHjh3HlmX5JkuyLqOZOXu9f5x9ZvYcnRmNbMl2jPbznEeamXPZZ+/13eu+tnQ94w+Zy6aq1f9FBFVFVbEWypUIa3mpqn5JRCxYm74mo5mZntPsu0ZNROr+NrtX+r711yTds5m/t3L/Y+n/TPdvZZ5UBVTr36G+70ZEEBFjDM8WkS1BaDDGYkyIiCB6bP1+srTwRD3IG8QQ6FBVRE7O4PpENdPzZyZkbUqkze7f6LdWxiR55tyMn3UgMYD697fJoaoYY0402ZzeAKlNngEit2pVBx1VsSDmWDgIZBG5tNivxqvv9L5IC4Rqp92jlZU94bAzcwUz51wkfq7W31s0a+xNbb7UjUelSjqnM/c4WUtBAgjjCMTMJUeYi3vNbtLtMXOBGYbpmMTa2b+w+hhoJB4motY0spn9eC0AJEPWxR2CqvV/Myd7AE7NyTXzvkAkXC5+fzvL+yxwkOOe4HgCGsnYsVhSvyqdHGAc//Ob6RFmXsFR00OOiXW4e0zngmkuEs+jNPx9gYPMwaqcWLMSOfZkAWM2q166j61eN9/gyBq7Y7dmRQ2vjS2NGH6O27wApJVVpVVFdj5FpEbXNulXiwRj0taAlvSW+RC/GjzUzPCu1psj61u34u+DmgVSFwByTHORjLtIgGpNnIrBY+dUB5hLFt/AP2KTZ0z3icxO1EiLK1liz2xB0eJC07I/KdsMrlV9ci4XuJ97Jb2ma8TEMRvH2bx2qkYIZgaAWF+hFRF7DMYF9zw1jYnKtPT9cTgYbfaDpjs6M+5nwDl9rWDFOp+IgFv8FgByHKJWAoz4sN5KaT1lec4IvwiMub8VYMIdY9P/1wmgJCJTQEVEKqAlESmCltz1VgSrqhW3ihZFeI2qvrb5ql/3Un8uoj8QkbyIhGKq/gUDhKpaQDV0nwvAIvfXgHYDXe5zXoQuYkdrwc1fAegAOoF8E7DMBtTGN2LUuKdvldTq25/O3CScD1Bkg0QwIqjjJs6aZalzQoEjyqJHyKPAIXcMicgwcMR9n/w+KiITQCn57K6vtNrXVkJPbBwZg6quE5HXtipaiehtIrJFRIwJxJoUqBRtvEJoAw5S86QbIO8A0qnQ4wDV5b7vccdi97kPWAV0Ax0qdLhzO316kJR5yxcNa4tdrIMscJBjVSdFqjpcLGIJgTFDVvU/jchOVR0DDqvqAQeAA8AQMOyIvDSfVpxGAEnL4MnhVkujqj22ZUuYIiJ9IkICjprp1Nc9TEs6SfqxLjKh6I5Dquyahe5mNAZNApIeVV0eA8j0AkuAtSLaBzJqjMEEtf6LCKe7FjLvOog4dIhYjBGs5buByHcBjBEvfCFTJDBZVpj5BkiaIBMnJ4B1bMTEykkLCrkDnYEEHMaAiGlBAppdjNds9Dh3rlUHLlUdghhc0w0RBgdwjDGu/1LXw9PVFxLOl6NHUv/HhCFO76hxlJlMjbMV6VrRiVq0XE3jIKpSFyRoYuWkJQJJOFNCXFVOJTaRs2YFEI5j7a6LVJbWABf3PQZHErTocdXT1mEYIrZxVK0n/9YNltFM+bhernYKXN1U1ixaWeLMXAGikZe8leDEZlGyIjHXUNUZzf9ZUQQ1a54DiPFHR2YNgkaOzKz3bxTBrFJT/erfOWzwTvVAsOhpLWaF2mz1c45UVY0tep61QpLfZwCJ/ze+NvYyx4MdH7MDic5AIKZlU7JPUFlgTZ9vNR0R0Ixw0/kkKQXXHN9qWwWyp0urG1utmz/HOX1I12IQqxxgOojSz4tqtDCN1WsmnlsyYOipDpAZ8xUiF2RYnwNBoqDNAJKZiOdYLSANV8QWvOM+MFSn96kpSMRgPUenSQEh/ZzG+pJmEtG0cZ6JWzjOVnunqI7IZ8pVqTezm7rgxenP0xnHu1muzZPR2hXOtBomsrcvZ8YyqAeSFhXJ+R6gtI2+mchU5QQxayQwUkfQ6XGZCwfnTFwqizvF5zdaiRVM8rttCtRszumbigVIbmIyQGJaml//qyyDx7RxFD2luUgYRdpC3BTVSaj5NUz188lo2QQvvq9i+oqGZBBl/A5WBdOCon28IGl4fTU12XrBjrFTtQrazIuzoqJl2mrvgSEjfERtK1w4DdxmqcXNPtdfKygWc4pqMmEUaebL1w+MJ8t6/4sYX8s7ac0HR2oVNkpTxdymOcZcA77Ve/rgqOXNpHSHLNCmrGK195EUQNy9baaIZDT25BdUdR2wFlhO7DwsEPtKEi+9BUqg1kX7jorIKOgwMAA8JiIlJ3fb2rtrnXjp62IigppTUx8JbaSvn75iaCYrBMZEzFdnM/EnmqM4Qu9Q1eusUslad+sXg6AkYkdF5HarIJq9YCTvG6BJYFUT8+wxmFs9U7KqXquqnbYW82X9vz53FJWHRHjABI11jAQUKfFtnao+E3gKcBGwSVV7HRjC2XJvb8E5pKqDxE7f/wZ2ishdwO4sjmyMIQhjaUQxpxwnCa21n435eb1PbhoAFDA6BnyVU44dmmqf3bHKKje5VdFMz8/w5XNrRXTAGHN7vdLe3LgwX+/hVvrPWrTbj5xt0j4APKBWYkujWF/68jiTGFVdD/wq8CLgCpCO+vtLi4CenqiVBH6q0gv0uq+viUGtVkQeBm4FvgRsc2uMBYtEgkiASIRKcEpxktDFQ5n6yZdMK4pa+iU49S0PVqmoat4vGtHcumUG/GSuVpTzuWgNDAh5i/arapdqjOr639NA171152g997AWo8oLVO0NwIuZRdj78eiEqQ4bx6UuBP4U+CbwKeCb1sZRFmLBmIBTzdBlcAF99R7jmcMYYj+GrXmCT3KrKbJ+kJ3JMFH7c2Za8muc0P4LJSfmeOPuB/82EKO8w1prrLVYy2XW2u9Ya7+hKi9Ri8kajxPx7q5vxh0vUdVvqOr/WGufFkVKVLG4Pp8Kam2NUkTUzmalPJl55K0SWfP1wBzLKpht2jsOwsoq7ZP6zrR4n9F6jmGxMa39qbX2HlV5DirGy3Q6lfTGZwI/UNV3RVF0SoIkjBUrrVo9smT7YyPGU4E5aiYBpkMtVOKAvGA24DghK25jnDSOGxOjqv/qFP2W1KbZhO/Mk3HlgyCLrbU3xvqIxla8U0AfCX0LTbaJ8vgG9kRxjnRUcL3YZKadb4+R0COkBpKT9P6p0qej/quo6j2qellNNNM64wvHEbkwn6KYqr7TWgGiG2PKNBgTnXSQmNqANyqAJg24y6nTZjtxNnl1EWiBb0zjPPMsrjQDtgpY70jpkD9S1cvqAaHVQ4w5ZhF5nsGRHO+0lsuiKCKKIlSDk64bGjGzwtL0DuupUxVGMyfTTAOHSJC5ILRSdDotZh3rBGYaQo6hVrGqVtw1n/fBUQuvN9VjlsCw/uGcgs0sMjbLYXkM7TZVwUZKpVLBWi8Y8mSIWE0SljImtLVzTz4nMXWxSdM4B3aayGDUZq7YJ3olnS4e+hRIlk6xRVVXAa+fCfBNnLsTqroT6AceA54gdvSNZdzPAJ0isgpYBmwC1rm/HY11wKzxywyKXAVcr8onbRQLtRCgYgnEZKgA80uPs84orNGdOWXA0jwidjpMav4uy6ncpjtrM9+rA/iHVu/l3fMBEfkmsbf7IREZjItWtKxvGu++RlV7VHWzqr4QeIGqXFZf8CErerk+UCC5F/AhVflkHCsG1lqSIirG9cOeoEiOsJVV8dSvWhFzfyGVCKSK+Pt2aCJe1ctkzWtVpahCLVGySpws+bhmIBgCbhaRa2ggXvqVE1XVisgXgH8IxGwRkVExWhXBZmnpsp7uYEEOqHJArfmeqr5b0V9S1RsV+cVp1jTNFt8hjj5DtRPhlRj9ClGAH/oUW7gUqZvtedRBjqG64Km/0rYuWtNK4eZ0ZPCsdJVjsky13HqAX8kuamfSlWJuEZFzDfKmQMwdJmDUBBC4wxjFGGl4xJmQ04/4d4294V7woYj8h4g8W0TfUR9Eqa2MgVHVV1lrqURTVColwFSTzJKiEebEcxA5oaa9+TF/trYhzlwkas31IjKbjX2yrI3TC0xbQAaAN4nINwOJiSwmauNZfOWYxOWayBa4cY+zDjEKtko7HwE7DPKZWS0QYl+AmjDMmUqhLaSQCylFllJFMWIJgiAGCYqdRytry0r6QjuVOaxpBPx7ReSXDXLABMmKH3gcQI77XfxFtAYUiRMNaoT7t6DPhriWWEuJdBp2CPaqdatXfG/tyqWsWbEEay2Hhkc5cGScQ0eOMlkqo1qZ14U8PF2I9XTfp6IZOJie9mvB3m2Mea5BKjE4ElFJqiHlgsxJSIcvPtW4uCDGVp01wJtAX5u9M1hN/LV5g5koUzh81HCgcsmLX3vN3ZvOXFVZt3o5qsrufYd4YPsefnD/dg4+vo+ORYVYzxQzLzQQstBOO84mottE5NkGsSbAxEp4rWjdieGoDixO3CKudHmLqn29iMlICxbUChycwFbg6uc8let/80Xvfs6zz3l6Ic8oyg/KZf7pif19xd6eJZQrMD45xdDoOAEGK/NTX2UBIKedyGcBeYYHDmuMEEhspnKKrpd4dZyOXqkgBF4ado3wjXH7GNdA8ncicp2qNUnhvFqUhmICYX13N5+4+a089xkbMdC7t3/slYExdLYXfmPJIvPBs9cuesuK7vO/7gRL7vzRQxw5OhFbLOeBiywA5EnfLDX/nUXEvNEgQybAiIg1RkyinHsIcjRpjl/9dPdI0rHTIKkmXooFlbucryVUtSYmv7I7LwQ13PGND7B6bYEf/XiA8clJShVrAPJBPr9i+ZK+TWd1/9uSxTzl3A299w0cXMeeg4f48YOPE2FQW4E5TlgyCwT2ZBen6mrl3meQz7nvrDFiDNXqjUm4SEywdm71D0+8S31HOvzke/H3OZASmElsNEZb/wAffs9L6Flb4L9+8AiHxyYJFi1i8fIu2hd1EBnLwaMjbHviIEej6FtnbewuLF+1hGVLu71CF9lWy+PhKgsAeTKr59521gYhEPPWuIYuSeyVTQ5fKVZbX6hi7pVbOy0mywPND3H+N1XFRhFMVDhr81pe9cKn8ejWo6jpptC5GKuCLVdoK0D30jaMMew7XOLA8JHlJii/oKujHTSoA0hW2aTj0bsWAHI6cJE4j36biPwwAYcxzlqVCqepgkPmt0/1f+v68LNUh2BolI3nr+GMxYsYmRxH8iERilImQrHuCAKDtZaooga4EomM1OkxMUnH5mbj8tyPL4ByASBP8uatljcjtZW7ts2CzLnYUX+vRhuANoyoPlB3Yj4PU5a+vj6oKJNTxWptM6wiWqZcLlMuxSExbYWAMAwBCmqNDYI4mCgOpfVV6gScwTSgLADk56RFrvCbRa2qfr1GBI5EUvV/1Z54f1EGQQ4lherims956Cqwc+deijlhWcFMDzzV+LtyyaKViFxoCAn3BoEQhjFg8mEbBiEMlEUdIW15JapMoLZEEAQYE9YBZcGK9fPVtgI7q0SpIOZElCnyuUjLVTZHwcTG3yiEchl62vif/3qAB3eVueDMHr63fZQwDF3dYSEXhEyVIsbGyyztbqN7SRtgvzo2XgaxdHZ0sKynk56uDtasWkpHW0CpNMXI8BCP9w8yMDSFmoLTVWLHqohtabFYAMhpIWKZHyZ5a/X7jjQm2EQCUpmbsJlW7xHvKiYWLCYIIGewOsXkzr38xUf/nls+9haWFIY5MhlRyAWEIRixlKOIrs42zlrXgRHzl7sPjO8aODhOQMDFm9bwC+eeyfKlnaxZsZj2NkOlNMHoyBB79q3i3kf7eWDbHkbLgph8Nde/FZAsAOTJjY5ESr5/xlNt470j54+L+LWFq3pPpQaoeNdcohDOPZN//vtbWbOizIfe9TZGQnh06xBTEyMYDenoqLB5/TKWdXV9c+DQ2J9s3XGEUqnE0q52zjvrIp57xUYiW+EXzl39i21CB5ZDIyMj9+4eHKRvZTfrV3Zx94M72LHvCGoWIQRU98axsgCQ09SEhauvvzVtrWlE940BYecIKOmgSQsSkewLQ30ZVQMWMR2ARc9az19/7NvsfryTP3zLr3HV5T2I6aEikIdtlv2f2dVf+avte49w948fY3JynMsv2cgLnrGB7vautyPcMD6mq4bLxbCQD1m0dPFPNnd3/cXy1cu+srxvKRPGUjLKjt1j5POFlhaLBYCcHm33zEQr04I6pbrJ6txnVtZnEDa2B9WCG2Nrk1l3Fl+947+57Y47OWv5Mi6+ZP2973z7b7w8kNH+8SPD3Lf3IEdGYGKyyIXnbOAZF/fRmV/87/37Dv1S//4pOz5ZMfHTLJ2L2y7bsKbryyt7lj/70vO4YffAEXbuHWHnnqOUShPkwkWxIUMs0qB/CwB58jcrIsONNyGdSRGd/7Tj1sQ4x1AiS1tXF6KwfWSM7Z+/dWDVOWf3L1/axmSlwk8f2kpvby9P2Xwm11y5kTUrOj+1e+/BF+w9WKQ4pWZRexu5UJicVMZGp9hhh6hYvX7Zkq57nrJ5wy07+oc4PFxk38EJRJRSqVJ79gJATsuW7Cs/IyGqcELrg9TC4P1CeM0ApKiJq+eLNXR0LqZ09ubwzh88THtHnsmJEoNjg1zb18sFZ69h3equvnKF39mxZ5jilFIodGCtpVgMKNsIYwzFScvA/lGWLOr98LrVq29Zt6qP7kUH2Ln7KKYaBhMu6CCncSvSYD958AAhJzctrl5Rl6YgtoqzBAu0F3isf5BcW55ckHO+H4stT1Iuc+0Tg4cZL0b09iwjNDkUQ6likUgJwzhIs1iMmCja5SuXdV2yef2a++5ZtptSaRdt+aBhCaikLTgKn/ytlFiGGoLjSWV3kHhbOSNExqJijQqICtYVS7fWYuPdIddEUUQuKBCIMD5RZHRsgvi7gLZCSFtbSKkiHBkdCyeK9sJ8LqCrI08Yhg60UdP+LADk9BCxKqcDOJqRqKWSVYL0aKEtH/9qLWFoCIIAjSJUI6IKlEoVcoGwpLNAKAxNleKhWlTIu4DOgGZFzRcAcnoAZJqYcjo0FQsmokJM8JaIQJy8GFlsSe9YtqSdjkLA1NQUJlDa8ooVSxSVKUUlwNLVlaNnaTsmsHeNT44xPDZOnIacFveSLTFqJWlN2uIxw0aVTx5Azb6EZ0OrS1bZmlZjmlqx4DTe0ar19/O3hW7lHU/cNGi6PK1p1XQmGhJJxWowRdlU0GAcEWHXnv2Mjo5zZGTi3lw++OHqlYuoVKaYnCxSsZYgUJQIWykTBBFLO8OKQT4xNjkxenRikkMjI6iWyYUGUQgkIG8CAvH3i7EYggUlfaGdrPVLZ3ZaSgWRsisSFxGGeSrlCvuHRtl9cIQDR4ZZ3L38FRvPXLrXWsvg4SLlSmyviLAUcoY1vV2sXb30MaHyB/37h3h83wHGJo7Su6yLpZ0FzljdS3tbjnwYMjk1xf5Dw+w9MMz+I0cplkoLAFloJ9OqVePO0zmziaN4xyZYt6GXtlxAmM/zxL4j7Dk0wnfvf5TuZV0sWiIDGzv6rly3uufmrs7yZaNHJ6lUKuTbQnqWtLO8u+ObSuUNR8aL9uHH93Lw8Agrli7lvLPWsqGvhzNW9ZALhbZcwFQ5YuDgCI/s7OehHf3sGRxaAMhCO/kgSf6Pt9qoUNvn3cCRMV74a89jZW8XZRUe2zvIw7sH+dnu/Sy572GGR/dz9eXFLX0rei7vXdZ9zcrlHc8vlbTTWrvXGHPH/qGRe3cNHDI/27GbxwcPs2blEs7buI7LNm/grLW9rF6xqE+JrohshLV269Gxia0XbVrJ5m3L2fbE4AJAfu5Em8zdvU+emGVMrAeExlCuTCGlItHRCYimWHbOKq688hd7XvOiq3oXdxUOjBVLbN+7mvbObXzv/q08uL2fvXv2MnDgKBectYa+3pV3hGF4h0Y2zkAUw96D+/np1sftnsFDLFuymKsuPZ9LNp3Fxr5l6zsXhR+yVF49enSciakS7fkcS7o6Hri4s3BjX++Sb1563llmASCnE/HPEhynRJ8jS3lsnPziLspHi6zcf5SzrzmHt7ztpTztjLX09q14WldXbkepxPci5TN9q5fe3lbIIVa579GdPLbrEAMHjrJt1zAruveh1Sopsdi2e3CQPYMH6F3Wzbnrz+QXzurjknNXbtKI7+/dX+rZNdDPxFSRcklZ1B6yZmX3RatXdH9j1bLet56xovfTCwBZaCe1GaBzcRdaVjg8wm+961o++PuvhraAnw0e4IHdg1iVzrZ8+OI1y7pevHb5ks9dfcnZb+zMhVQmijxkhUcf38fh4UepVEJsVCEIAtracuTyAePjRymVKmxcu5pz1vXS27MU4FuP7BhZ/sSeIxCCSBuBMRSnLAP7pyiXipyxqu1TSzq5cwEgC+3kNqtYW2HiyCjPfclLee+fvo4H9h7i0MFRopIQaQgo4+NTHDlykIs2nvEbPV2LBq44d+27+/ecyZ5DI4QmrngSBgaCNgCKkxFTU1OIhBTa8vR0LebMvrUsXbz49SOjrN9/6Cj59jz5tpAwyINUqFQslbLl0EiFtnyZxe25mxcchQvt5HIQhXzFgBX+v3e8mtEJZU9/EXQRQa6LXK4dE3aQz3cgBNz/yOOMTBx9+6q+9r5NG9extnclYWiwkanmwYiACWIlP94aGwptXewdOEw+Jy86cPAoU+XI5ttCgpyBIAIjqAmQXEBFlYOjFazlmQsAWWgnTUFPTLq5SLn4qqdzyeYcu/aNEpJHbQ6wSBAQBCFhaMi3hUiYo1gqMVWauLY0VWFRe472Qp4wNJjAYgKLmAikglJGJfZLhsZSKBQwhrPLFbCoCQKp6m4VYg+8xUAgVGwZlMICQBbayZWwLBwdOcq6DXna22DocIlCe618qfEqiQZBUL3GEJwXb83mOAe2Wv1EVVHC+HBpyeVIyOVyAP1IBdUIjGKJ4tpbdjqARSguAGShndQWqVIcK5KXPZTKMFEaJUDcNtBKacoyVSxTnIyYnCgzOTEVi2bGjKoqlajE+ESRSC3qInOttVjrQIClYiOUMm15EOE7S7oW0V7IUyqVXE68RTWqc1Y6cG5bAMhCO7kAMSDtIT/67k7GI1jbW2BqajLmCFYJQsWEAblcEp4esKg9JJ8zP0rSiIulKaKoTBAIbW05wtBQaAtoyxsCE2+zF+/VCFa5Zc3KPCuXL2ZioshUBCUrRNZgI5CoRHlihAvXK0j0ngUr1kI7uUq6MbSt7GFg5z6+d9/DvPTKc/j2XXvItUEU5YgsRFapRJZ8IGw8s4vlPYvGoHxrsinQ0iXtLFuyiN5l3fR0tRNFEUEQcORokYGDRzg8PM7kVIm9B4YYPDQ6um511/M3ru3+tqrw8OAYaC4W3CTi7MXCL1y8mjA0nxYT3b4AkIV2Ulo1/sooRSKWda/i+re8i5ff/y+8+Fnr+K979hOJxWhAhKVcjuhZEnLG2k4M0St27h7j8f4RJktTXHhOHxectYYz+3pZv3opbW1tcdTv3v08vGMv92/bw9jEBD95ZCe5EJ57xeY7+pYvef66tUtu7l1WOLtSjqMj83kbdrRLsWLthw7un/yzw8OT4QJAFtpJB4nJhZRNwMhjRS58yRf4ysdfxHOvXMmUhZFSwJEjZbRs2bCuMBbCG3YPDt25Y89RxoolzuxdylUXn8Ulm89k2ZJOVi1bfFZbW67HWgYGN/QOXLTpDNavXcEjO/bSP3iA8eIkQ0ePcuWFZ92xoW/FuWuWL36xCE8HCqq6fXRi8puHhsf773vkED9+cP9pG4u1aoH8njwgQZQxYwnPOYetP7yN577obp75zGfyspddybkbloxdcN7yr+Zy+Z9a+Nz2Jw6OPrprkG/f8zBrl6/g8k0buficNaxY2nX9+OTUDRPF0vnjk1MWk6Ojo7Dlsgs2fGxNb/dX7l62mG/94BGeGDjA3gND7Ow/wAVnrWHl0p5vVsryTREhl2vDSBzR++Bje/jOPT87bQHStUB6T7ZmUQysPoPDpRK3/fu/c9tX/pnc4txdb33r69541eXn2Hw4zuCRMR7dM8zSrk7OOXMpl1+w0SxfUvjy4eHitYOHx8zYeNFOFOOC1d2LC1e0nRF+6YxVS59y1SWbbzw8PMmhI2M8snMP+w+PsHXHABv61hNFEVFURkSoVCocGh5lR/8h9h0aWeAgC+3UasuWLeXwoSHoXAzLllMeGeGuLbvs8IhQWByYBx+7316weQPnrl/FZRduYOXSwoeGRku/+tiuQYqV2PFnI0GsZWhogm2R2jPXLHv7sqVL7rnwnPW3PrbnILv2HuTA0FGGRorc/+hglZOFYjAm9qWUIuuqyJ+e7bwFUntytuHh4TgGPx+Tpizp5oG9e8zP+p+wbYrtWt3GRleT94yV3X3FKd6+/+A4R0an6OjooL0tRPOxH2NyqsKBwxNGJLDnru+9af3qZbduWNtHb89eBg9NMlWuONOxRTWkhIUoNguLCG05OW2LNixwkCdhS7zgcekfEwPFCKKGMN9BuGgJk+UK1tpkE51r9x8+yoEjJfL5Am25EFWJPe0B5AKDMSHDw0U7UbRrlyxefGHfsiUs7+6KnYpGqFU0sd4R9yXSGCDFBYAstFNReY+3l6vtmmVNhZyrhuh2oeqaLJaYKpZpz7chYqiUlbGJKSYmI4IgJB9v5xaWyhHteS5sLxToXLQoLiSh9fUjsnbeMsDYLPrd8SQZ38sWSOw0AEeynZyLt4p306pvizoKRFFtM1ITQD6fj0lbQwq5PPlcB6EJKm15RkWEnBEqVqft35jefUpVMaT2jJuhNExnDJL6Qlvzt1vqbFiz4GocXZLFQU5W3xbasQPEmDhCN5+Lo3Pj+CqXqx6XErqzt2cR2AqlkiWrxlsAACAASURBVKVcCQiCgHw+Tz4IUSPk20IKbbCo3VhgCxpiCYkqgM6sghvB9Pumtqwj3vtaAbpFpEttjSib7U/d6gpxrJu41EDpdj+1Fmt5jqrG+2ZofV/q976LZl3jaqGdGGDU/ldMEIEUUYqIVqhMKYeHjnLg0Ah7+oe35HNsW9PbyejYBJVKxZYrAdZCRcGWSxw8/AS9S6FjEV8cODg8NHDwCNse30voooQVg2LiLaczDgNsmyVRbq7nGKah/DYTYc8V54nBoUSRYq395RauWOAsp7CSnsyRElGJDKPjcXYgUmF8bIpHdwyyc/dhjoyUKZV5xaZN3ZXzzuymYtUUpypMTSlTU0JxKuDCTWtZt7Z7wIj9/Sf27bM79h5g256DLnR+5vp1IbB3JlSniGgtxNtbS1B/3vEMzCz2uKs719okvNlirXao6jULpPbkAEOaxpLvrK044cVCaDl3w0o2nrECkYAdew4yPDrOfY8+wdqVS1m56rkPLV2ce/q5m1d8pndILx0fnwQgCMQGoZp8zt45Nj75qv1Do6MP7djLIzsHmJgYJ5fLtVR8M1TVra2upO4lqokqcWKKY1TGHNPgzMVAVyKLjcW+35kN91hop4ZYVd2NSg2iEREBTE3AVARhhedcfgGXn38mh4cn2LRuNU8MHOLI0XEe2L4X+cb3uPL89feeuXbFU5cta7+up6fjWUA3MAh8p39w6OsDew9xz4M72LprL1t3DsYFrjVqiR5DYEt6X+pmq7mqvlhV322txSQJwFkv22AD+7ltBmvLjnuQB/6o2SSILIhTpyI4REFUY44RVeDQQS6/4nxe8PRLWbe598JXveBp13d1Bvc91j9y18joOA9u38t3tvyMvQeG2D2wj9FSmRXbHmPNmmVfAP2C9wQOHDzCE3uOsGPXPrY/cYiBQ8PkgrBlqSWMt+XlXlQvc72eacW+SFXXqmq/Kli1JMwji22mB2Su2HKcjK/YeFwN8Fqgr/FkLIDjVBOvEnAEGtdSL06UyXdYvvK59/DyF16SFF5fW4m4aWxCWdu75M4Na5bcsGTx4q3DY5N8//4dfPcHjzA4MsHq3qWctWYpop5+KbDziX3s2nOQ0XFLmTy5IMR3BraigwDcSYbvwN/905MRjbX21caYv9KYPlE1dYjMAsdcDm6id1Rt38Z0qOpnGwFSRFnQxU9VsUoJRSiNTaEjI3zvGx/j8s197BwY5ZHtA3R0dlIsFkENZ6zsuvqc9csfOfvMxWuuuvjsgbHxIj/bsYydgyM8tu8g9zxUqYr+1Wr8EqJaQCTEKHGm4mxkFGda+9dGnKOBqHSDtdbY2GpEFEUpZdnOCUASANTuSwocyf7c/NcC2T1Jm42YGjtKZWSUd773/Zx7Xh/ffmAnW/cNEXa2U6pYlnR0kc/leLz/MP37J4iE21avWMTyZV2s7OkkzIF0LGEq38W46WAyWMxEsIiJYBEVbQMEoxxTnVUjIhb4CbCNVkSgmPDXq+r11towwUFCwEksTELYegz7dPhAq4GizlrlDouqfksT8bAB91hopyorqVBpn0KLZdo29vC21/Xx2I4jhLqIQthOPiyQL7QxWowoW0O+YxHb+w9y4ODIZctW5K9uK+RYsbQbrCLkyBEQqolFtgrkrUHExMTj6EDFVI+WAGKMwRhjReTvWlaq45X949baS6IoCqPIGmuVKKpxjwQsPlDSPo+0L6Q5KFLAsHK+WnlQVa/O0n8WwHGqN1NTFER5zmWbWLakk6NjZZKYCCEgMDnybQU6OjooFAogQRxEGOjz2ttztLe3YxSMVXIKYcUSViwFhJxVclYJGm0K1QJIjIgYEbHGmE+ISGWWOyn9SFVfa60NoygKVbUOKGliTwOh9pkWQKGhjcBG9KqV91lrf6aqFzpDwwI4noR6SFXKKpdZ3t1FW66e7lQVtSFt+YBC3lQ97WWNsNZ2AYRhSGDi/dUrpTISWUIbb5JrHOM4HnE/4SDGgeOPWngz8JVwNZ9HzZeBK2zcsNaa2GqnHkexztPNDICoAwUOFMZGPM1ae7Oq7lDV/+0PYgNwbAMmFkjxFAdJYCAw7Hxi0H1ZrirxlUqFyclJSqWIiaKlXIoIDCxeFNIW5vZ0FHKojFcV86SW1lxGaYRVh5+qFZFPqOo7mplL08Torr0Wy7XGmG0Y/Rfg+9banwBDQEVErL9ZfPJvrfPVe5l41ZDlwGaQzar2WcAVwKZZvOydwI3APQtkeMpq53GKrc1BZ8BP79vO48OWjWev5aEHdsaVSYxQKBjQ2D1i1dK+KKCjLaRC5faxiTITY2mVYG73eAiNiQP9jDHGmcdeo6r/3cw812Sjz01YeVfMm7QE7AJ2AwOqOgQUVWNDtXePDlQ6VLUT6ATZpKqr4v9jM3QrwEhxjzewkJd+6nMQDTFRCZsXivtHecef/R2f/shbWLV6KWNjE6g1mCgg0gCIaCsErFiSxwTcfmSkvPXg0DhDQ0NYW8GEBWwl1lys57+Oi0IcJ0AAmxC+iHwPeC/wviww1Ps6BBWNrQhpL7qavKpuAjZpyggmIqnNXmxDz/tsdomN/+pHgAHg9SdKhp4Lc/Zct9nEt51ULqICHQWCnjb+5R9v5+qLLuB11z2TscmlbH90L5EaEEtHe0Dv8jxLuszWUsW+fPDgGPsOFjl86AjWWoJ8gAYBaBzErZLQ4XFyEDHYOPFQEoefAf6Pqm6sJ7Imu/cKcfl41Xh3uZSlqmY1iM9Vr9cz6dLNiEeniWp8AfRGRxhrGl9rUiExrYXd135PWz80NRjJOVq1x8z0fll9bdqfjIWrVrg5/jnLcXoio5cbvZNqDcCiIVrOEbVNYUpL+cO33cS2R/fw6298AZdesoYk5jYHAwJfQLnx8b0HuPehg3z/R3s4NNqFulq8cRHr6TQieuy7a4WiMThA4hxdNdZaGyq8QdWgVl8N5FGNbcqx7cvTJ2rcQLzYLM2ayCyl2sg0EvOJIGuQ48ENqquQa18Bfj0m/oRCTSZR1PVB8cyKiX6UVvwdmIgXgGmg1unwSL6v3lulJfm4fk/21sAx7d0yub444MgpEeJfA78FmwcJsF0BxY4cH7/l6/zDl/6dpz/tHDZuXHbfB993/Q2R8tDBgyOjQ0NFHnj0EN+/dzuHx/YyWhmNx6xcip2BmVw0rvObOV4zASSOajTVTps4kb1irQ0l4A02kkHgfwH5mvhUj8i0e7+Z7uJf10ilahbwWM8BqqLZXwLvSF3T6W8xXG/RMHUiX8JBZlq9RSSuBj59nSIbIh4H0cBlsGntsyPbuF9ZcWy2Acc6OaLgfAElDgUSoAPCCO1uY6gS8a27tlL+p527Ors2bNmwYVllfGKYg8MVtjzYz7Yn9jE0NlYdJ2MlW8KwxydlhWkdINZJLCJBRVVDEX2HWvmpqn5UVfv8lVdbnJimYlIDPae5iS7pr+wWkTcB/0ktbCa5R28aaHX3S1nQpusyWe+j87qaCqksy6qYZFPcQz3QWOJQv1MTEE3m3kyziKLuXQQNc5R7DLRv6rrz3u30Pb7fFETsA/v66d9/NA5v8iyi2UaAObBiqXqsjlousEupraiqUdF/UuU/1JqPqupv+oQXJ9LrNN2CWWYXzmIFtMaYIvAJEXmPMyMnL2ASK5mq9jQCXvPJlFnrQidUbKmKULZON4y/DqvbkFXF32q/tKHIOt8cIuOZJvO86noVK+ZoAdrz5p7t2yvtO0NjAMGgJn5HM29LVh1AaitpbCI16VXXWqtGVUct9rewcjPwu6p6Xf310zR4Q4Y8PKNZI3sArftuG/AVEfmsiOw0Jo4ESC7xvPPGWdDquEdieTtOC5BplRBmu5p7CntnEoyZtSKm7trZqmh1MnSPBs8M6/onGcKphiAVhIBQhAiDFcHEfDYeG4kQ5pdjhum5jws0JJ1MRBZrXaS7sdifYOVNwLsVvVZEXqboZUA3NTnQacqSrVj65VbU+CCoA4gYSkA/8E1jzG3AnSJqRKgYIymFVt1KqlhrC6h2oopK4J6dbPBYo7LE6lZvR9CGiV8utN5GUdREd9ZsM19iDah+TtdjkrohiA0Yjrvq7JT7WRFtRiRCqyJhq/6pmgshAYcpAYXay8bnBsQ7TsXXxYtrEBtt6zboTD7D/IuTYVpZqh+omKPUnIlYMEZFrY1kwCifViOftNb2qPA0Ai5C9WKsnI/IetLOOpNwlNj3EusxmgCiAgyKyH3AAyLyILDVGB6I/TViAStirJiavpGk+kZRhFGDtRUjIkWFC4Ai3nuB1KUGV7PZ6gjDZ9wG1ZSPRiwicotqnLnWong1qyQEEX2++v2Sert2fX8loxpIPVATI4C2AJz51GGMCbG2MgRypZvvkMSIWmMtVZAg4vSyk2hEWHzVH/kTM22A/PI+teDCmu3epuz47lwDdKtqF65UENAhInlHLxYoAUUwE8AwcAgYkzglzNZPuOCDomZMqBFLEscVVarRxDYNev9cn7tXazCZBHT1vp8EBFGkVCrRMYkqInFgnTGJObweM2rjaoEWdbFsTYCnkuL6gjHYIAji/yVJEvPnxmQyIj8Db74U/VpAasIRTVOfll/ZxBiDGGqbdSoZRp35Ex1D/+ZZ4Mj6rUZAQuitoJYkAlMsMOTCS6Zba/ytS5uID4l24SKO67hcrQ+1eq7GGCRX7Y9x19ppYpIKCV1IhtE5Fi3V7WtXL24FgTmG7ESdUWepcUOtGkkSc7bL2ZmmB03jXlUxTo4RxPO3VIsoxtSchJn0BojUnLgkOodn5auXquy8W+3CZqDwPc5pWbfO0eN6HbiJUeNZtxpMQOLdnKFARPIsF7+l0whDUkUjan2zmTKzutVLnXxfWx+ydBoamntnNzGSaSquBy51nmDxRNGM861vLMjKs6k6N6vvlB2T1KqS23w8WgVfok+YbJqoSg2mTjKts8TVXXMCdJDZmCj9gECR6Uad+vz1uIxL3cRZrYak4NVcrS/kVu8Dqe+bL/7UT1wiUvgEnK1waz0gGuYLmGmrf8y9jr0SZJYIeywre8qsXQeiWIRh2hjU+1WO3QqV5VtqJUSnrh8NuGvtp7kt9jGHVqzm1szpSnxr/gBbrZ4VBzaqBWME62TMeDKDat5IoxVLRKyqmLTMmZhuY/adfGetD5CaFUQca3b/W20wSY2JfH5MoW7sYs5rsxxpqf9NTb63dYuKQaoLRqN6ZbV8Hm0oWjeKqWsEjGaE3Ig2TnVPfzh9MEzLhJF+6WarU5wmWz3HRFFkjTGEoSBGUK0lVqFp+TzWR5KCdTWfTY3j+H1xzzKeYcGKiDGBWGOMCYLANhJ1ToSMnuWV97MtI7Wu77Gs4VKiM4ndgcMkyWpxRfKY09Z0Gbcq1+fwTDPzZgFkpmDK2ZiXT/3o4iYAaQYOX89oNYSk7lxny9d44teqSuiUsZ1RFDnd0nhFHrRPVQvuNhMiMigqmACstTGxV8WzjAmMn0UUaYeq9qpqXoRDqnKIQK2IdbVZ59dx1lAZ1foQidjab/E5grV0gHaraqe1dtgYM5hwhBRQbDXRTHW5tbYj3iVAhkRkwhdtNKtvTcSoNEDc/31Al4iMAgMz5Qg92ZszJ0pDYMxGbk6bZjPM/wVV2Q7ssMKjEfICdRYlf5KstR+3lu3W8iguLyW2jlXNuVZVbVaOuy9uiMingO3AI6p67fTU3oQDzXT43Kr1I9kmov67tIhTr8PFlkAB+A1V3aOqD6rq73ogIB294I3F54HHgSeAX5yNwt3iHG8G9gA/A77dqrPwydzC4/ZtZXvF0yHbyQ95iAMvpS5bMA1C05ts/6Oqy6sxX9YiBr9gnK1+X+VWYlIhG8mzu6tEGClofC9DKzkgMs102gqRZXvjk4ho8QIPHfgkcVJGAGe4a0OysyNtxsLUUbPtmA4fqIlhZHq/jTdPklKSNf1/Z71lbma95EkPkHrdYmZQNA1FF1vn7a2y9/gHV0GlLgDZ1nOc6iCPejrRqC9GqctejKIIETFoIj5onV/Edeh3ReTDLoBxty/CJE+vNFA+0/J1o2ovs11BU5VmkrpkWUUGbga+5sZzQK2L91FF4/4bj4PE4+7o2QXdmFivwy0I2aKxNY3F5ixRy/U3RLCImfdYqFOYgxybzK1Ks5x1t1JVydmVLiWzXpZvCnSf13qOiwFHGFe7FbYfuDdl3Rpw53lmUPpELKo6CoylnlWguosWwyIy5pyUFaAHuAjoUtVRVd0CTKQMBB0uegDi6uIAm5xocm8is7t7bQaWE2+BNwhsTa1Q/fFRD0QvDqzP3QN37YCvE2ATX0mUFpH63DuOuWsebjKlV7jzB0Rki4hsq3F7tSYQjO/0XQDIHDWRVA5E85gmbyW7R9X2AXeKyE2o3AS61pPt7wVeViNO/TjwNvfb8x1R73XSxgCwhiQyNH7APY6I8oicocqoC5B7P/DHTkRMzMkDwDtAvuiJJ/8Aci0wSrwV9buB691q/5QYrHwUeDPTo3DvBd5CXOUS4G0i8nEHwD9T1Xd7537I3bfDE19vBEpeXyoe4C51HOmKlLhggLvcc7emgPQP1NVrlq2qfNCZ0GP2ZzFhW2Cb5dEsAOQYOEwi0TbnNPVmXs+CAnC1ql6dwd4vc4TwCvfZl937vFWzzyV/XSMidzhCWe+tyA+hOuBMTf+mqi/27jPmKrCsBf7RfffF1PO6iOsFb/Ku2w28BPg9N+4HgDuAs4Cnub7/OAYtg0BnAzHufcAfJmB179QJfDglJ4fuvTa7+yZtiwPDta6fVwH/Bpzjfu8EfpQC8JiqbgY+74A3TR4/ba1Y823izLSnJ7L07C0gY57dfitxxO4ZwJ9751zrJtemVssO9/dm7/vneX34pcSIAHzKnfvbHjhuARap6mLgGU4EclyDgtNXxry4sU0isltE/kpE3isiJRF5t4iErkjfuSLyOhF5uoi8w12LiPy2d69ED6q4v2eLyP8Wkbz7/CYRWeaOv03pTkX3rN/3vvtbEXmWu26FiBxy55ztDkTkfSLS4f7fKiLnichqEXmqiAw38oWcrs3MNzgafp/ltMpwQE0b/Nrnl3uy97up3856bZN3+xy1xIvXegB6g7u3Af7eff8nro+jxLW2kkqNdwMfd301IvLmmtGj2u/HROQcEfkTEfmAI/jz3W+hM0E/0133EWAxsBT4vxl9ThQJn5N90XuXUeAG4DHfpO4p+88ANrhz6nZoTQEKEbnWA/nLHEgmROQnIvJbc7H56gIHmRlBmR7cRoOe+j6xfA2kThto8ekDnry91inKeSeng8hdiBQR6XZil/MX6SOqusf5AfYAfyIiReKw/ctdH0uedepj3spvRaTggJzoB68Wkf8RkYMi8m0R+VURGRWRgYxdgBNCvtj77lveb8lxh/d7Uk72IRG5W0SGROR3ROSfReRBESmLSK83pmPemCTjtC0lTt358waQE6mD2JlxU+UmY819EvHvsdvgmIopfN4pugAXEueiJGPxGW8FTuT8TieDp/qiY26RKcb7wFQBUgLZnXpmCfRvHOG9yekd3Q6gVwPXOL3k2Uzfebg9Y0Ebc0DzQ19HM8a8j9iptzn128NOB0n0uoQ7Ju88OuN8cfq3E81BhoEBL6d3U1axYVW9zPu/P8sef5zN28eOtwLv91b2//AIJFFIByVuS0RkqYgsJc4QXQwsAn7X9c9PGQ4bLBBfVdUXqeqZqvpCVX2ve0frwmL+/4zxSLzoA953a5M4M8+Tfr73+6j7+zVv6+5bVPU8VQ1U9YLU/ZJnHPLuX0j1ozPtrzndPelzApCsKusN8jSsiNxH7FdITJObUpvmXG+tPdvFgaCqt2XnOxxXG3DWHIBXOsUeB44hb0W92/2/yokeYw44o27lvdlZsjanxjNMj62rN7xdVX8MfMzd4z+B/+OMDXUe/wbg8otx35A6pwN4jvd51LPsJZ/fmOJOfr8Tq9Uu736/k5I2bmhF11wQsVp2d0ji6fBAwsdVeKkb4XWq+qiqvdOZEvtU9TIS7yFsRcR3/lWcfF+Yg4Xgaym/ACLy5RQBvA/4jvv/HuBDIrLVEdaHPFPsH2SYPNNjO6iqZ7vzLwUedZysE/gDb0HZ2uQ9bk/M1M6E/G3PKndTynrX4QGjyx3XuXt0eucbbxE4BHzQjY1xQO4g9tFcCvxx3UJI6zkhCwBpAhKhrsDCdxFuVNUPaa1O6NX1rFvAmK3As+KyPlXRpTuxAhFXThzzssp8n0NXhh8k7Zj7G08PSRT/r6TAdBfEfRWRPkdU6bCTFzrdIRSRzY3CU9z/zwe+7a77lDt88A8l4lqqv/57vNzjJFe7I2n9nk6R/P0k8E5P92rUetzfW51J+/VuXN7vxqQEHBKR5Sll/rRuQdu6px+vgJX6WF9tRMR4DCFedxD5vnPQKaoF0CUiEgFHRGQLyE2IvF6MmYpzIapiWq8Y2W6MuV9EbjXGlESqOw+1i8jjIvKAiNzmLELLnf9hq4h8TUR2eeJe2Vmh9ovIDmPM5521R70030hE7gb+U0Ry7pgSkX4R+QbwKmCLZzVaLCL7ReRhEfmOiOxJgWUn8K8OTG0i0uMsTfcD/ygirxCRw64PgYh0icgjInKrewdEZK+IfCnRhdx7bhGR14jIl51/Y6u7ZkBE7nQm3C7XvylnPXuNiNzlxucxEfmhiDzhnnGrs64V3LjuEZF/EZFfcWLyYRH5lhj5ThgE1WIXp2OTrmf84XHeYXp1Dh8gqoJFiSr+7reGKImviixgOxwRTfgSUe0+1VwJoy5Rv3q0tsWWbcVf0yga19uMtFqozoXGWC/l1dQXlxC/zhcZyq3PqewcEZiZwVpYAIpzJhmIEARCUk3ltBSxZnqxWUerZinoVjFBHHYdh6YDVtDA7UKKnagXSdJ+AOOV9k/95iJWj9Ud06jffhpsUhfMEbLNKort+SKm+QmytwWT+QjVmMmUXpzzFXZOsyxPPeV/bnWQVD6IX3hZFJd/EZ8TBI5wXKmaOoKKg7irlfTEUC03mVQzrR5qjhnYfuh+swlKsviahb5PTxabPvnNcr3negVuXq3m+OsJz2W/W62hfDK2bgibBQxmTW4jUKSrZtSbfePVOEDicjsOEHE1RIH0vhzV7RSSnKJqhUqbJFRIXC27aQGJRv3O+q1RlY5WCX/2O2HVV4FptZbuTOdlebkb3f94CK5BIYljvk+j+fL7fjI4S9jKpMxmNU4XUEhz/2TLuBgWtUJn9YOs3jGzCDibCTrWiZ2pYsfxrLStgjwrrHwmkM0EhJOtO2T1Pysp7WT1M5xp8Gca2JlYd7NcgbhwwvHrPs2U7dkWdW5EbK1y2mai1Gy4TNZ1rXCgZhzkVHfspeoSnBJgDpOOzEQAM8mLaaJodaU+FuJtRoiNKy22DsJGIG927Uz9mq34lAXURmOfdW5W/zyDwoz3PllcxLcaqk6vSHmi+xmWy+WmhNSKfJsyhWaeP5MM3IqYkvU3bT5NV31vbdOc1om/1WuzxINm4TIzgWO24q3/3n61l2by/8kEy/Q5lWlVNU8GhtMAMc1WIRGx1SrbqfPS1f3EKy2aZfZsJso0K4yQHkh/4v0i1n5VQd8s24r4Nhudq1G/ZiMmtqJAN1q8ZupHepwaFaVoVXGfT/DU+inub83IE8/piQdwaC2bQc8SiTfwSW8mU88NAHhYhF3G+DZ+ktjC60HWAGtBPqgq22I/R1I2p7YaaKpodE0Z98PXtb4sZ/VapZaYWK0YfjVIF8iotdwZVxKX6u8iUKvePpPy3Iq8nu0zicdCLwP63C22EEcDp2/QRxwLZkEGQO7199tNGy6yi2HUyvJkA0WS/iTzA8jVqnSJMCrCnSK4jVulukVzc51zrsFiU2A3rsoml6nqWmAC5G5VHVM1VS5yoixboar+gYi8eRar6/8VkbfUVwpRVPV6Vb3JfXWrn9DUzM+QBuFMXKPBStshIt/2zjsjCeWuq5nFzDVmWxWvskQi7/iat+HpIHCGK3/q3+Ja4tguQ1yo4akzmY+zRKdmnDajlFCHqn7bu8ca4iDKGQ0JzYwRxwcUrSvMF0d0K9bazyc5OMaYK1W5t9kekvPpB+lMwdk0UrhFpJLkCKTY4rWqerP7/neBv2kkamTpDG4QGlqCZhIlssQN3/jQyr4n0mRP9iy9qpnvwuVUrHWLSK+IvAv4QIrAK7X9FRn1RcI0qFuxpM2kn2WEuCSbGFWf10qB6UZAOdb9GOulAuv3d1htHGahol6B8hNr8g0R81niXOayjQdvGcrvVTk4fFJhn0IbyqTAFrVqkz462XZAVd+gqneQ1H5KuL+tdwJmVXCPJ8dm7s+RpVxmDPaEqi6qr3mriNUZAdLItNrIEpeIJOk98lx9SGxcwN7PKjaovl9EPgf0ezJKhepzGBMRbGSb+jIaKdXNFqKaSAlOXFka1+4lFBitvoU221I5Yxynzcmst1UIHTSqMWuJ6GutxUY+zRy//+uYARIEwR2OsGuZZfA2agLf7xNHnNYFwqUVQOLCBlcAYwp3+EBIxSQVVPUXVfUKVc0B/yNxzsewV5S5E0iy4Laq6hhxWHeS/PMTFw3sD1iX94wDGf6Bzap6Da7wG3Gy0rYGntxVwIUicgVxuus+FyV8N3E51JY4T2q77C+LyDO8Z9hGvg73uc+97/nEmYuPEOeKPOAK2dmMfl/j5gBV7Seeh4GZ/FWpBWGzG+s1wF7iXJAtGbRTfY57xmb3/C7Xz682oLlVInIVcLGIRAK7ReQB4L5qZqQV40nwqDXVDY8SbnOi/DphGIZpS8cmL0rViMiFInJfbZenugm5DvgLEVnldbUiIj8B3igiD6dWtetU9YMaJ0pV3CpSUtUhEXmftfbT7hnXukLMqOqfO+K/PhENHFi/DrzM9acT2OdN9Bm4Ig5uMG9T1Zf6hOsG+A7i/Ioxr49/ISJvA/KJOOLOrQA/FJFXMUOBfK3MQQAACntJREFUiIyJs46gXu82/7QzxBp9UFXf2eBed4jIa4grPyZpvpcR55ZcltGPj6jqO7yF54j7zYrICm+bvE435tdm9P1O4DXE2+olBoYkJ+VW6hO3kvYQ8CJq5ZEgTsb6vbjavuRV1RoR6+jgh6h5haqrmNmAS8SBrCdOBzHGhPiHSFA93Hc2/burWv5KMJ8H0xNvJBl8VyTYIhIYkeAKMPeoSo9X2fw6Vfm8qrhKiOZuMN9FNY9qj6reZK19pQNq0elCBniXA8c2aumgBngp8JstyOg/dufiCPt7UJ2Ea+JqjWqstaG19v3EWXMFJ7a9hzhh6jE3ic9U1Ztmsx+6iLzHgdoCf5ea7ar84HHkD6rqO12O+KjLWf8zL3/8amvtzakF7UceOB5W1Ts8Ins78FH3W4laxUXrFqnkuT9T1Wu9OgB3ur9GVa9W1ftVtSfjFV/qwNHvjA3J0n8+cUZi0t5OnLjVCRRV9QOqeqNa6VcroOZpuIIZx2JmnzeANJDRZwqb7lbVLztOM6iqK1X1ucCV1DLiOtzkJrnmNzmCr7jV6HnA85Dg18GEqISofNmBadjbPqAiErwRzHlgNooEt4Ox7vfXeRXMx5LtBrzjbWAudf9vAXMOmOeDORPMQFwRxZwP5jp3zuvBVNz/zwLzATB/CeZKt42BBbM52dIg43l1h1tMPgLm3WDyYCyYz6q66u7Z173NW4SeLxJ8AMz7wPxCvHWpCcFc4fpiVeUz3hYLt6jKBe4dz/X68Htg+pLxdP1IDsC8Hcxab5zO9MbpDvfcVWD+IKO/FTB/C+YMME8Fc6N33+d4593gnf88keA9IsFHiPPxK0BFVS86dkX/BACkQRE3m+Hoe4k7N1TVLwIHqqX3RT5JXL0EVX19suqparcTU+4Wka+6TDorIl9A5COI/D0if522LLlst1tM3BCRj3t96WrwXmPu+jd4fb8hVlIpOcX5FcSppZ+jVsHkhcTF23JOrExEjFe6a/36Ua0609aKyEfc9XniVNbOOrm+3qp2JdAmIjm3Iidz9Nqqcl8ryNBJrVDDGHFxu6Rtc58/6d6zq87xEP9Nimf4170x9Tpv9Lj2df74uv7mReQG52TuEJG/8ZzOBRHpdJ9f6LIkcyJyn4ewa6sGI1cwY65zlI7XzNvSTlEpi84FIlJyMuIfq+ofu+/95AxDrULHOqebhCLy7+5/X0m8MWV67fZA8pg/sU5hT66rNCDO0UQxd5/HYgdn1V9Rstb+kFo1+MRHsRXoE5GXAK8TkYtc3xPd6ljyZ9YT1+V9DXCb++7z1Or6Vot5e31YS1w3+DXExRKWe9tBG1WtuHMv9N77jgwiugW4xcn7JWrlV6uZkJ5inoxbumjEAHHOfa+IrPPOS9oBN9cVtwh0ufHuckSffN7qdJdrgJerxs5UNN4CwulEdqbQpvQCekIA0kr+QBaQ3HdFpmeyDXggIbVyFWeyAKUGKG3tKXicrjLDKt45w+/p699OrQh00U3wNgekV84mXMR7t6T/tztl92rgVxB5Jqmdolx7n4j8qe+PcuC+V1Vfm3CUjOeOHQsBzMbZl9omwjJz2SiTSBPA//J0kuR9dzlO+sqZOEM9KE4gB2k0CM22/xWREU8k+xPg057VK+t6v7zMxRn9eDuwwSnCb0pPdtok6RU5M40G0YFpTFU7E2doSoQ83+lLVlX/Ffgu8H537YCI/JaqJkXk8sCv0EKK8gztDcDjIhKqam/G2G8WkT917zVKXKX+u04+r4o43gI25v1/WQYhPwd4harmHXHubrDg+aWBOlPj3+XM3n7lxvws3nnCca6PekB+HXC7qrpMUX1lq6kA2tRfMw86SANRyjQARvL1V73Pb/CVe3d8FvgW8Nlk9fSufaWrIJgQ+dmq+mFVfTPwG1l9bBi9qzrNEuT+JoC41bvuQ6n73kxcGC0xHy93pl1U9V5V/Q/vWb+UrJqtRvY2OG+A+kr06Xfc7CxLVlW/7ixJiaXpV5Ix8+6/NSFmZ816aWqsvqaq1xPvRzJKrbyoT/wAt3rvemNqnG/0FpY7PIDYWdDYek8Mu8uBI/SskTStnuml75/wcPcGSLXp1TnlzNrmFO1fFZFLVfWnzi9RBt4gIme5y97r/vaLyC3OLNsJ/JS4inkI9s1xTq0a4LecztrpBe1lVwaZRoC1YEePDb8b5Do34G9zTsLHiTez+UU3yYdA76RWqNqZf/ntuAQR5wNfUsWKGFMfaFkLk0g2Pqs/6vrmfCq8V1V/G3QVkvZCS7+3Ov9SfB5bgEucibhSv6jFC5Qq/+r0v9uIw3z2Ar/uAeCLDpwdHtFab9+VDzvjAc4U2+cck+f5i5YqN6YX0Baadcq3dfR2lVsM7wIuQuRLNV0o2a/SX2CCk5roFVpbcUQXoRoBNq9q3QqRbJ6p1f3JaxNqXwO2C+yLRbhEVS+pTZpYEfkEcVlNN0j6JuLiZNcCq5wjzHhA/ISzKPnKPaq6LtVnX6/wCzJ3eqtLdYsxVX0R8GUHjjSHGnS/h86Of6vrXwfwdxm6WOLwK6hqsQnXWOfEAZOytCXlgV4O/MC7JjEm3Ot0lZe4sfqMd/Ox6ruL+A7B2+OFIN79yS0Evn5wd8pK1emAVkgZJ17hDAcdHihsykL2mMd5klWgN4Ouurxru4gDNm91YmqXiHwmNXZJXy/1RL/1Ne6R1o9PKEBsmq31i8hfOrl1IpE7s1JzReSXndf7l0VkrRvwAVfY7M40cWlsWr1GVV/jLCIlR5hfcgpsMik/FJE/J96Y5pFUn7eJyF+7/jyS6BrAB1xxtNGUDP2fqnom8GYReaqbsFHiXZT+xqvIDvBrjsCe5SxJQ8SlRz/pVthzZ8qlcN+9x51bcaVK/19xR44TMRCzzQohhKCjQ9SIigKJZ9BQgsQPEC0PACHKfQEfgA/sO/gBogYKtCIeing2s17bmQgUUu0mTuJ4fMx4fJQucoa22NxlSulAzr/gct+TK2ib++xCG8j4BK37/EK0eu5o+yW/74V+54L3lliMmXiySgG9AYCdcmEvtHwGgH2Zdh7JM97F2j+q6IE3AHgQxv4w+OrOcM6ciXU6Fk/hJwDMEHEKAKeIeCi4bMu125TSntD09b8sCG6eXFtJNesppbnRpyKs9RTlRRsOgNzmOfJm5VAPttYkxf1kuP8W08QhfcEVLJfTyzJb0aJHLsDdNE1YaCFbF61A+vLarXWLF1FcU2eqCFXRBecmsNqhqzw2RLmBAUPGeQo24txxYOYFrYnaZL32/3jptxPMcz4AoDXKxJzrxbnFENaiKiqTo2C5lkENxtfwXiNJU0gCwnLfQnu1PXQ36URCICQgdC2u5f4N8QoEhZWycZ+ThSDvoyhYUveRsYjSx3dwnSvP9TK5T/8Rp1h5wIpI2iUN7w2yN3hRSLa3lxIxhCdwA4pErAiJMyisvsW1cH2bV1aufVD/iSwBF5w1Tl7kMBfW1nLjkqPdLdxq9jf4rxhwiBXovmfEfRAvIUgPkrWR+Lskmfp7opztqOBAAcM1VTwUDEfP9XDxBDbAk513cO3UqWDsUEkNoDWPJSBD8jo6uPHKlP4Amo4H/McmJ6IAAAAASUVORK5CYII=" alt="UTN"></div>
                <div class="header-titles">
                    <div class="header-title">Universidad T&eacute;cnica Nacional</div>
                    <div class="header-subtitle">Sede Regional San Carlos</div>
                </div>
                <div class="header-tag">SIGA</div>
            </div>
        </header>

        <main class="content">
            <div class="dots-accent" aria-hidden="true"></div>

            <section class="hero">
                <h1>{{ __('Report of :title', ['title' => $title]) }}</h1>
                <div class="date-pill">
                    <span class="date-label">{{ __('Issue date') }}</span>
                    <span class="date-value">{{ now()->translatedFormat('d \d\e F \d\e Y, H:i') }}</span>
                </div>
            </section>

            <div class="table-card">
                <table>
                    <colgroup>
                        <col style="width:8%">
                        @php $colWidth = round(92 / max(count($headers), 1), 2); @endphp
                        @foreach ($headers as $header)
                        <col style="width:{{ $colWidth }}%">
                        @endforeach
                    </colgroup>
                    <thead>
                        <tr>
                            <th>No.</th>
                            @foreach ($headers as $header)
                            <th>{{ $header['label'] }}</th>
                            @endforeach
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($rows as $row)
                        <tr>
                            <td class="row-index">{{ $loop->iteration }}</td>
                            @foreach ($row as $value)
                            <td>{{ $value }}</td>
                            @endforeach
                        </tr>
                        @empty
                        <tr class="empty-row">
                            <td colspan="{{ count($headers) + 1 }}">{{ __('No records found') }}</td>
                        </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </main>

    </div>

</body>

</html>
