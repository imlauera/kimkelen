<?php
/*
 * Kimkëlen - School Management Software
 * Copyright (C) 2013 CeSPI - UNLP <desarrollo@cespi.unlp.edu.ar>
 *
 * This file is part of Kimkëlen.
 *
 * Kimkëlen is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License v2.0 as published by
 * the Free Software Foundation.
 *
 * Kimkëlen is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Kimkëlen.  If not, see <http://www.gnu.org/licenses/gpl-2.0.html>.
 */ ?>
<?php

class Person extends BasePerson
{

  /**
   * This method implements __toString method to print the object
   *
   * @return string;
   */
  public function __toString()
  {
    return $this->getFullName();

  }

  /**
   * Returns lastname, firstname of the person
   *
   * @return string
   */
  public function getFullName()
  {
	
	$names =  explode(" ", $this->getFirstname());
	$fullname = '';
	foreach ($names as $n)
	{	if($n !== '')
		{
			$n[0] = strtoupper($n[0]);
			$fullname .= $n . ' ';
		}
	}
    return $this->getLastname() . ', ' . $fullname;

  }

  /**
   * Appends Identification type and identification numbers
   *
   * @return string
   */
  public function getFullIdentification()
  {
    return sprintf("%s %s", $this->getIdentificationTypeString(), $this->getIdentificationNumber()
    );

  }

  /**
   * Returns the Identification type string
   *
   * @return string
   */
  public function getIdentificationTypeString()
  {
    return BaseCustomOptionsHolder::getInstance('IdentificationType')->getStringFor($this->getIdentificationType());

  }

  /**
   * Return the string representation for the birth country
   *
   * @return string
   */
  public function getBirthCountryRepresentation()
  {
    $country = CountryPeer::retrieveByPK($this->getBirthCountry());
    if ($country)
      return $country->getName();

  }

  /**
   * Return the string representation for the birth state
   *
   * @return string
   */
  public function getBirthStaterepresentation()
  {
    $criteria = new Criteria();
    $criteria->add(StatePeer::ID, $this->getBirthState());
    $birth_state = StatePeer::doSelectOne($criteria);
	  if ($birth_state)
	    return $birth_state->getName();

  }

  /**
   * Return the string representation for the birth city
   *
   * @return string
   */
  public function getBirthCityRepresentation()
  {
    $criteria = new Criteria();
    $criteria->add(CityPeer::ID, $this->getBirthCity());
    $city = CityPeer::doSelectOne($criteria);
	  if ($city)
		  return $city->getName();

  }

  /**
   * Returns if this person can be set to active. This will be only when is not active
   * @return boolean
   */
  public function canBeActivated()
  {
    return $this->getIsActive() == false;

  }

  /**
   * Returns if this person can be set to inactive. This will be only when is not active
   * @return boolean
   */
  public function canBeDeactivated()
  {
    return $this->getIsActive() && ($this->getStudent())?$this->getStudent()->canBeDeactivated():true;

  }

  /**
   * Returns the directory for the persons photos
   * @return String
   */
  public static function getPhotoDirectory()
  {
    return sfConfig::get('sf_data_dir') . DIRECTORY_SEPARATOR . 'persons-photos';

  }

  /**
   * Returns the person photo full path
   * @return String
   */
  public function getPhotoFullPath()
  {
    return self::getPhotoDirectory() . DIRECTORY_SEPARATOR . $this->getPhoto();

  }

  /**
   * Delete a person
   * Also deletes the person user and photo
   * @param PropelPDO $con
   */
  public function delete(PropelPDO $con = null)
  {
    $photo_path = $this->getPhotoFullPath();
    parent::delete($con);
    if ($this->getUserId()) {
      $user = sfGuardUserPeer::retrieveByPK($this->getUserId());
      $user->delete($con);
    }
    $this->deletePhysicalImage($photo_path);

  }

  public function deletePhysicalImage($photo_path)
  {
    if (file_exists($photo_path))
      unlink($photo_path);
  }

  public function deleteImage()
  {
    $this->deletePhysicalImage($this->getPhotoFullPath());
    $this->setPhoto('');
    $this->save();

  }

  public function getIsInLicense()
  {
    $c = new Criteria();
    $c->add(LicensePeer::PERSON_ID, $this->getId());
    $c->add(LicensePeer::IS_ACTIVE, true);

    return (LicensePeer::doCount($c) != 0);

  }

  public function changeGuardUserActivation()
  {
    $guard_user = SfGuardUserPeer::retrieveByPk($this->getUserId());
    $guard_user->setIsActive($this->getIsActive())->save();

  }

  public function getStudent()
  {
    $c = new Criteria();
    $c->add(StudentPeer::PERSON_ID, $this->getId());
    return StudentPeer::doSelectOne($c);

  }

   public function getIsActiveString()
  {
    return $this->getIsActive()? 'Sí': 'No';
  }

   public function getFormattedBirthDate()
  {
    return $this->getBirthdate('d-m-Y');
  }
  
  public function getFullNationality()
  {
      if(! is_null($this->getNationalityId()) && ! is_null($this->getBirthCountry()))
      {
         //Extranjero 
        if($this->getNationalityId() == Nationality::N_FOREIGN)
        {
            $country = CountryPeer::retrieveByPK($this->getBirthCountry());
            return $country->getNationality();
        }
        else
        {   //nacionalidad otra. Para los casos donde nacieron en un pais, pero tienen otra nacionalidad.
            if($this->getNationalityId() == Nationality::N_OTHER)
            {
                $country = CountryPeer::retrieveByPK($this->getNationalityOtherId());
                return $country->getNationality();

            }
            else
            { 
                $country = CountryPeer::retrieveByPK(Country::ARGENTINA);
                return $country->getNationality();
             }
         }
      }
  }
  
  public function asArray()
  {     
        $school = SchoolBehaviourFactory::getInstance();
        return array(
                'email'  => $this->getEmail(),
                'telefono'  => $this->getPhone(),
                'persona'  => $school->getLetter().'_'. $this->getId(),
          );


  }
  
  public function getPersonalDataAsArray()
  {  
      sfContext::getInstance()->getConfiguration()->loadHelpers(array('Date'));
      return array(
                "apellido" => $this->getLastname(),
                "nombres" => $this->getFirstname(),
                "genero"=> ($this->getSex() == 1)? "Masculino"  :"Femenino",
                "fecha_nacimiento" => format_date($this->getBirthdate(),'dd/MM/yyyy'),            
                "nacionalidad" => $this->getFullNationality(),
                "legajo" => $this->getStudent()->getGlobalFileNumber(),
                "pais_origen" => $this->getBirthCountry(),
                "codigo_pais_procedencia" => $this->getBirthCountry(),
                "nombre_pais_procedencia" => $this->getBirthCountryRepresentation(),
                "codigo_provincia_procedencia" => $this->getBirthState(),
                "nombre_provincia_procedencia"=> $this->getBirthStaterepresentation(),
                "codigo_localidad_procedencia" => $this->getBirthCity(),
                "nombre_localidad_procedencia" => $this->getBirthCityRepresentation(),
                "direccion_procedencia" => $this->getAddress()->getFullAddress(),
                "telefono_fijo" => '',
                "telefono_celular" =>'',
                "mail" => $this->getEmail(),
                "documento" => $this->getFullIdentification()
          );
  }
}

sfPropelBehavior::add('Person', array('changelog'));
